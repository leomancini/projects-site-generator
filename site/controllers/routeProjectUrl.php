<?php
    $url = $_GET['url'];

    $urlComponents = explode('/', $url);
    
    $projectDirectory = '../../projects/'.$urlComponents[0];
    $projectManifestPath = $projectDirectory.'/manifest.json';

    if (!is_dir($projectDirectory) || !file_exists($projectManifestPath)) {
        header('Location: /');
        exit;
    }

    if (count($urlComponents) === 1) {
        $_GET['directoryId'] = $urlComponents[0];

        require('../views/project.php');
    } else {
        $urlType = $urlComponents[1];

        include('getProject.php');
        $projectManifest = getProjectManifest($urlComponents[0]);

        if ($urlType === 'link') {
            $linkType = str_replace('-', '_', $urlComponents[2]);
    
            foreach ($projectManifest['links'] as $projectLinkObject) {
                if ($projectLinkObject['type'] === $linkType) {
                    header('Location: '.$projectLinkObject['url']);
                } else {
                    header('Location: ../'.$urlComponents[0]);
                }
            }
        } else {
            header('Location: ../'.$urlComponents[0]);
        }
    }
?>