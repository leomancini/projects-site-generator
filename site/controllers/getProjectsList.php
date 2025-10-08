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

    function parseSearchQuery($searchQuery) {
        $parsedQuery = [
            'tags' => [],
            'yearRanges' => [],
            'textTerms' => []
        ];

        // Split the search query into parts
        $parts = preg_split('/\s+/', trim($searchQuery));

        foreach ($parts as $part) {
            // Check if it's a tag (starts with #)
            if (strpos($part, '#') === 0) {
                $tag = strtolower(substr($part, 1)); // Remove # and make lowercase
                if (!empty($tag)) {
                    $parsedQuery['tags'][] = $tag;
                }
            }
            // Check if it's a year range (e.g., 2024-2025 or just 2024)
            else if (preg_match('/^(\d{4})(-(\d{4}))?$/', $part, $matches)) {
                $startYear = (int)$matches[1];
                $endYear = isset($matches[3]) ? (int)$matches[3] : $startYear;
                
                $parsedQuery['yearRanges'][] = [
                    'start' => $startYear,
                    'end' => $endYear
                ];
            }
            // Otherwise, it's a regular text search term
            else if (!empty(trim($part))) {
                $parsedQuery['textTerms'][] = strtolower($part);
            }
        }

        return $parsedQuery;
    }

    function searchProjectsList($projectsList) {
        if(isset($_GET['search']) && $_GET['search'] !== '') {
            $searchQuery = $_GET['search'];
            $parsedQuery = parseSearchQuery($searchQuery);

            $projectsList['projects'] = array_filter($projectsList['projects'], function ($projectInfo) use ($parsedQuery) {
                $projectYear = $projectInfo['manifest']['startDate']['timestamp']['components']['yearNumber'];
                $projectTagsLowercase = array_map('strtolower', $projectInfo['manifest']['tags']);

                // Check tags - ALL specified tags must match
                foreach ($parsedQuery['tags'] as $requiredTag) {
                    if (!in_array($requiredTag, $projectTagsLowercase)) {
                        return false;
                    }
                }

                // Check year ranges - project must fall within AT LEAST ONE year range if any specified
                if (!empty($parsedQuery['yearRanges'])) {
                    $yearMatches = false;
                    foreach ($parsedQuery['yearRanges'] as $yearRange) {
                        if ($projectYear >= $yearRange['start'] && $projectYear <= $yearRange['end']) {
                            $yearMatches = true;
                            break;
                        }
                    }
                    if (!$yearMatches) {
                        return false;
                    }
                }

                // Check text terms - ALL text terms must match somewhere in the project data
                foreach ($parsedQuery['textTerms'] as $textTerm) {
                    $textMatches = false;

                    // Check in various project fields
                    if (
                        stringContains(strtolower($projectInfo['directory']['id']), $textTerm) ||
                        stringContains(strtolower($projectInfo['manifest']['name']), $textTerm) ||
                        stringContains(strtolower($projectInfo['manifest']['shortDescription']), $textTerm) ||
                        stringContains(strtolower($projectInfo['manifest']['startDate']['timestamp']['components']['monthFormatted']), $textTerm) ||
                        stringContains(strtolower($projectInfo['manifest']['startDate']['timestamp']['components']['yearFormatted']), $textTerm)
                    ) {
                        $textMatches = true;
                    }

                    // Check in credits
                    if (!$textMatches && array_key_exists('credits', $projectInfo['manifest'])) {
                        foreach ($projectInfo['manifest']['credits'] as $credit) {
                            if (
                                (isset($credit['name']) && stringContains(strtolower($credit['name']), $textTerm)) ||
                                (isset($credit['link']) && stringContains(strtolower($credit['link']), $textTerm)) ||
                                (isset($credit['type']) && stringContains(strtolower($credit['type']), $textTerm))
                            ) {
                                $textMatches = true;
                                break;
                            }
                        }
                    }

                    // Check in links
                    if (!$textMatches && array_key_exists('links', $projectInfo['manifest'])) {
                        if (searchInUrlsExcludingTldMatches($projectInfo['manifest']['links'], $textTerm)) {
                            $textMatches = true;
                        }
                    }

                    // Check in tags (for partial tag matches)
                    if (!$textMatches) {
                        foreach ($projectTagsLowercase as $tag) {
                            if (stringContains($tag, $textTerm)) {
                                $textMatches = true;
                                break;
                            }
                        }
                    }

                    // If this text term doesn't match anywhere, reject this project
                    if (!$textMatches) {
                        return false;
                    }
                }

                // If we get here, all criteria match
                return true;
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