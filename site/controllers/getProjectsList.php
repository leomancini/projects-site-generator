<?php
    include('base.php');
    include('getProject.php');

    $config = loadConfig();

    function getProjectsList() {
        $projectsDirectory = '../../projects/';
        $projectDirectories = scandir($projectsDirectory);

        $disallowedDirectories = ['.', '..', '.DS_Store', '.git', '.gitignore', 'TEMPLATE', 'z-test-project'];
        $projects = array_diff($projectDirectories, $disallowedDirectories);

        return $projects;
    }
    
    function getProjectsListWithManifestsAndFiles($projects) {
        $projectsListWithManifests = [
            'metadata' => [],
            'projects' => []
        ];

        foreach($projects as $projectDirectory) {
            $projectManifest = getProjectManifest($projectDirectory);
            $projectFiles = getProjectFiles($projectDirectory);

            $projectInfo = [
                'directory' => [
                    'id' => $projectDirectory
                ],
                'manifest' => $projectManifest,
                'files' => $projectFiles
            ];

            $startDateTimestamp = strtotime($projectInfo['manifest']['startDate']['string']);
            $projectInfo['manifest']['startDate']['timestamp'] = [
                'raw' => $startDateTimestamp,
                'formatted' => date('F Y', $startDateTimestamp),
                'components' => [
                    'monthFormatted' => date('F', $startDateTimestamp),
                    'monthNumber' => (int) date('n', $startDateTimestamp),
                    'yearFormatted' => date('Y', $startDateTimestamp),
                    'yearNumber' => (int) date('Y', $startDateTimestamp),
                ]
            ];

            $projectInfo['manifest']['tags'] = getProjectTags($projectManifest, $projectFiles);

            array_push($projectsListWithManifests['projects'], $projectInfo);
        }

        return $projectsListWithManifests;
    }

    function sortProjectsList($projectsList) {
        if (isset($_GET['sortBy'])) {
            $sortBy = $_GET['sortBy'];
        } else {
            $sortBy = 'default';
        }

        switch ($sortBy) {
            case 'default':
                usort($projectsList['projects'], function($a, $b) {
                    return strtotime($b['manifest']['startDate']['string']) - strtotime($a['manifest']['startDate']['string']);
                });

                $sortMetadata = [
                    'by' => 'startDateString',
                    'order' => 'desc'
                ];
            break;
            case 'directoryId':
                usort($projectsList['projects'], function($a, $b) {
                    return strtotime($a['directory']['id']) - strtotime($b['directory']['id']);
                });

                $sortMetadata = [
                    'by' => 'directoryId',
                    'order' => 'asc'
                ];
            break;
        }

        $projectsList['metadata']['sort'] = $sortMetadata;
        
        return $projectsList;
    }

    function searchInUrlsExcludingTldMatches($urls, $searchQuery) {
        foreach ($urls as $url) {
            $lowerUrl = strtolower($url);
            
            if (stringContains($lowerUrl, $searchQuery)) {
                $shouldMatch = true;
                
                // Parse the URL to check if match is only in TLD
                $parsedUrl = parse_url($lowerUrl);
                if (isset($parsedUrl['host'])) {
                    $hostParts = explode('.', $parsedUrl['host']);
                    $tld = end($hostParts);
                    
                    // If TLD matches search query exactly, check if it appears elsewhere
                    if ($tld === $searchQuery) {
                        // Create URL without the TLD to test
                        $urlWithoutTld = str_replace('.' . $tld, '', $lowerUrl);
                        
                        // If query doesn't appear anywhere else, don't match
                        if (!stringContains($urlWithoutTld, $searchQuery)) {
                            $shouldMatch = false;
                        }
                    }
                }
                
                if ($shouldMatch) {
                    return true;
                }
            }
        }
        return false;
    }

    function searchProjectsList($projectsList) {
        if(isset($_GET['search']) && $_GET['search'] !== '') {
            $projectsList['projects'] = array_filter($projectsList['projects'], function ($projectInfo) {
                $searchQuery = strtolower($_GET['search']);
                $searchMatch = false;

                if (stringContains($searchQuery, '#')) {
                    $tagToMatch = str_replace('#', '', $searchQuery);
                    // Make tag comparison case-insensitive
                    $projectTagsLowercase = array_map('strtolower', $projectInfo['manifest']['tags']);
                    if (in_array($tagToMatch, $projectTagsLowercase)) {
                        $searchMatch = true;
                    }
                } else {
                    if(
                        stringContains(strtolower($projectInfo['directory']['id']), $searchQuery) ||
                        stringContains(strtolower($projectInfo['manifest']['name']), $searchQuery) ||
                        stringContains(strtolower($projectInfo['manifest']['shortDescription']), $searchQuery) ||
                        stringContains(strtolower($projectInfo['manifest']['startDate']['timestamp']['components']['monthNumber']), $searchQuery) ||
                        stringContains(strtolower($projectInfo['manifest']['startDate']['timestamp']['components']['monthFormatted']), $searchQuery) ||
                        stringContains(strtolower($projectInfo['manifest']['startDate']['timestamp']['components']['yearNumber']), $searchQuery) ||
                        stringContains(strtolower($projectInfo['manifest']['startDate']['timestamp']['components']['yearFormatted']), $searchQuery)
                    ) {
                        $searchMatch = true;
                    }

                    if (array_key_exists('credits', $projectInfo['manifest'])) {
                        // Search in credits properties (name, link, type)
                        foreach ($projectInfo['manifest']['credits'] as $credit) {
                            if (
                                (isset($credit['name']) && stringContains(strtolower($credit['name']), $searchQuery)) ||
                                (isset($credit['link']) && stringContains(strtolower($credit['link']), $searchQuery)) ||
                                (isset($credit['type']) && stringContains(strtolower($credit['type']), $searchQuery))
                            ) {
                                $searchMatch = true;
                                break;
                            }
                        }
                    }

                    if (array_key_exists('links', $projectInfo['manifest'])) {
                        if (searchInUrlsExcludingTldMatches($projectInfo['manifest']['links'], $searchQuery)) {
                            $searchMatch = true;
                        }
                    }
                }
                
                return $searchMatch;
            });

            $searchMetadata = [
                'type' => 'keyword',
                'numResults' => count($projectsList['projects'])
            ];

            $projectsList['metadata']['search'] = $searchMetadata;
        }

        return $projectsList;
    }

    if (($_SERVER['SERVER_NAME'] !== 'localhost' && $_SERVER['SERVER_NAME'] !== 'macserver.local') || $config['useCacheFileOnLocalhost']) {
        $cacheFile = '..'.$config['cacheFile'];
        if (file_exists($cacheFile)) {
            $cacheFileHandler = fopen($cacheFile, 'r') or die('Unable to open file!');
            $cacheData = json_decode(fread($cacheFileHandler, filesize($cacheFile)), true);
            fclose($cacheFileHandler);

            $projectsListWithManifests = $cacheData;
        } else {   
            $projectsList = getProjectsList();
            $projectsListWithManifests = getProjectsListWithManifestsAndFiles($projectsList);
        }
    } else {
        $projectsList = getProjectsList();
        $projectsListWithManifests = getProjectsListWithManifestsAndFiles($projectsList);
    }

    $projectsListSorted = sortProjectsList($projectsListWithManifests);
    $projectsListSearched = searchProjectsList($projectsListSorted);

    echo json_encode($projectsListSearched, JSON_PRETTY_PRINT);
?>