<?php
    include('base.php');
    include('getProject.php');
    
    $config = loadConfig();

    function getProjectsList() {
        $projectsDirectory = '../../projects/';
        $projectDirectories = scandir($projectsDirectory);

        $disallowedDirectories = ['.', '..', '.DS_Store', '.git', 'TEMPLATE', 'z-test-project'];
        $projects = array_diff($projectDirectories, $disallowedDirectories);

        return $projects;
    }
    
    function getProjectsListWithManifests($projects) {
        $projectsListWithManifests = [
            'metadata' => [],
            'projects' => []
        ];

        foreach($projects as $projectDirectory) {
            $projectManifest = getProjectManifest($projectDirectory);

            $projectInfo = [
                'directory' => [
                    'id' => $projectDirectory
                ],
                'manifest' => $projectManifest
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

    function searchProjectsList($projectsList) {
        if(isset($_GET['search']) && $_GET['search'] !== '') {
            $projectsList['projects'] = array_filter($projectsList['projects'], function ($projectInfo) {
                $searchQuery = strtolower($_GET['search']);
                $searchMatch = false;

                $tags = getProjectTags($projectInfo['manifest']);

                if (strpos($searchQuery, '#') !== false) {
                    if (strpos(strtolower(join(' ', $tags)), str_replace('#', '', $searchQuery)) !== false) {
                        $searchMatch = true;
                    }
                } else {
                    if(
                        strpos(strtolower($projectInfo['directory']['id']), $searchQuery) !== false ||
                        strpos(strtolower($projectInfo['manifest']['name']), $searchQuery) !== false ||
                        strpos(strtolower($projectInfo['manifest']['shortDescription']), $searchQuery) !== false ||
                        strpos(strtolower($projectInfo['manifest']['startDate']['timestamp']['components']['monthNumber']), $searchQuery) !== false ||
                        strpos(strtolower($projectInfo['manifest']['startDate']['timestamp']['components']['monthFormatted']), $searchQuery) !== false ||
                        strpos(strtolower($projectInfo['manifest']['startDate']['timestamp']['components']['yearNumber']), $searchQuery) !== false ||
                        strpos(strtolower($projectInfo['manifest']['startDate']['timestamp']['components']['yearFormatted']), $searchQuery) !== false
                    ) {
                        $searchMatch = true;
                    }

                    if (array_key_exists('credits', $projectInfo['manifest'])) {
                        if (strpos(strtolower(join(' ', call_user_func_array('array_merge', $projectInfo['manifest']['credits']))), $searchQuery) !== false) {
                            $searchMatch = true;
                        }
                    }

                    if (array_key_exists('links', $projectInfo['manifest'])) {
                        if (strpos(strtolower(join(' ', call_user_func_array('array_merge', $projectInfo['manifest']['links']))), $searchQuery) !== false) {
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

    if ($_SERVER['SERVER_NAME'] !== 'localhost') {
        $cacheFile = '..'.$config['cacheFile'];
        if (file_exists($cacheFile)) {
            $cacheFileHandler = fopen($cacheFile, 'r') or die('Unable to open file!');
            $cacheData = json_decode(fread($cacheFileHandler, filesize($cacheFile)), true);
            fclose($cacheFileHandler);

            $projectsListWithManifests = $cacheData;
        } else {   
            $projectsList = getProjectsList();
            $projectsListWithManifests = getProjectsListWithManifests($projectsList);
        }
    } else {
        $projectsList = getProjectsList();
        $projectsListWithManifests = getProjectsListWithManifests($projectsList);
    }

    $projectsListSorted = sortProjectsList($projectsListWithManifests);
    $projectsListSearched = searchProjectsList($projectsListSorted);

    echo json_encode($projectsListSearched, JSON_PRETTY_PRINT);
?>