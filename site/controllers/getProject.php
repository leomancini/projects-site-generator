<?php
    function getProjectManifest($projectDirectoryId) {
        $projectManifestFile = file_get_contents('../../projects/'.$projectDirectoryId.'/manifest.json');
        
        return json_decode($projectManifestFile, true);
    }

    function getProjectFiles($projectDirectoryId) {
        $projectDirectory = '../../projects/'.$projectDirectoryId;
        $projectDirectoryContents = scandir($projectDirectory);

        $disallowedDirectories = ['.', '..', '.DS_Store', 'TEMPLATE', 'manifest.json'];
        $projectFiles = array_diff($projectDirectoryContents, $disallowedDirectories);

        foreach($projectFiles as $projectFileKey => $projectFileName) {
            if(is_dir($projectDirectory.'/'.$projectFileName)) {
                $projectSubDirectoryContents = scandir($projectDirectory.'/'.$projectFileName);

                $projectSubDirectoryFiles = array_diff($projectSubDirectoryContents, $disallowedDirectories);

                unset($projectFiles[$projectFileKey]);

                $projectFiles[$projectFileName] = $projectSubDirectoryFiles;
            }
        }

        return $projectFiles;
    }

    function getIconForLink($link) {
        global $config;

        $iconCode = '';

        if ($config) {
            if ($link['icon'] == 'DEFAULT_FOR_TYPE') {
                $iconCode = $config['links']['defaultIconsForType'][$link['type']];
            } else {
                $iconCode = $link['icon'];
            }
        }

        return $iconCode;
    }

    function getLabelForLink($link) {
        global $config;

        $labelCode = '';

        if ($config) {
            if ($link['label'] === 'DEFAULT_FOR_TYPE') {
                $labelCode = $config['links']['defaultLabelsForType'][$link['type']];
            } else {
                $labelCode = $link['label'];
            }
        }

        return $labelCode;
    }

    function formatForDisplay($inputText) {
        $outputText = nl2br($inputText);
        $outputText = preg_replace('~\[(.*?)="(.*?)"\]~', '<span class="customFormatting $1">$2</span>', $outputText);
        $outputText = preg_replace('~\[link="(.*?)" (.*?)\]~', '<a href="$1" target="_blank" rel="noopener">$2</a>', $outputText);

        echo $outputText;
    }

    function getProjectLongDescription($projectDirectoryId) {
        $longDescriptionFilename = '../../projects/'.$projectDirectoryId.'/longDescription.txt';

        if (file_exists($longDescriptionFilename)) {
            $projectManifestFile = file_get_contents($longDescriptionFilename);
                
            return $projectManifestFile;
        } else {
            return false;
        }
    }

    function getProjectTags($projectManifest, $projectFiles) {
        $tags = [];
        if (array_key_exists('tags', $projectManifest)) {
            $tags = $projectManifest['tags'];

            // Hide any manually-added tags that will be automatically added later
            $tags = array_filter($tags, function ($tag) {
                $tagsToRemove = ['github', 'audio', 'video'];
                if (!in_array($tag, $tagsToRemove)) {
                    return true;
                }
            });
        }

        $links = [];
        if (array_key_exists('links', $projectManifest)) {
            $links = $projectManifest['links'];
        }

        if(count($links) > 0) {
            foreach($links as $link) {
                // If github link exists, automatically add 'github' tag
                if (stringContains($link['url'], 'github.com')) {
                    array_push($tags, 'github');
                }

                // If live site link exists, automatically add 'live-site' tag
                if (stringContains($link['type'], 'live_site')) {
                    array_push($tags, 'live-site');
                }
            }
        }

        // If any video or audio files are attached to project, automatically add tags
        foreach($projectFiles['screenshots'] as $screenshotFileName) {
            if (stringContains($screenshotFileName, 'mov') || stringContains($screenshotFileName, 'mp4')) { 
                array_push($tags, 'video');
            } else if (stringContains($screenshotFileName, 'mp3') || stringContains($screenshotFileName, 'm4a')) { 
                array_push($tags, 'audio');
            }
        }

        if(count($tags) > 0) {
            foreach($tags as $tagKey => $tagValue) {
                $tags[$tagKey] = str_replace(' ', '-', strtolower($tagValue));
            }
        }

        return $tags;
    }
?>