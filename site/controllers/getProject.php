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

    function getProjectShareImage($projectDirectoryId, $projectFiles) {
        $shareImageExtensions = ['png', 'jpg', 'jpeg'];
        
        foreach ($shareImageExtensions as $extension) {
            $shareImagePath = '../../projects/'.$projectDirectoryId.'/share-image.'.$extension;
            if (file_exists($shareImagePath)) {
                return "https://leomancini.net/projects/".$projectDirectoryId."/share-image.".$extension;
            }
        }
        
        if ($projectFiles['screenshots']) {
            $imageExtensions = ['png', 'jpg', 'jpeg', 'gif', 'webp'];
            foreach ($projectFiles['screenshots'] as $screenshot) {
                $extension = strtolower(pathinfo($screenshot, PATHINFO_EXTENSION));
                if (in_array($extension, $imageExtensions)) {
                    return "https://leomancini.net/projects/".$projectDirectoryId."/screenshots/".$screenshot;
                }
            }
        }
        return false;
    }

    function getProjectShareImageWidth($projectDirectoryId, $projectFiles) {
        $shareImageExtensions = ['png', 'jpg', 'jpeg'];
        
        foreach ($shareImageExtensions as $extension) {
            $shareImagePath = '../../projects/'.$projectDirectoryId.'/share-image.'.$extension;
            if (file_exists($shareImagePath)) {
                // Standard share images should be 1200 width
                return 1200;
            }
        }
        
        if ($projectFiles['screenshots']) {
            // For screenshots, use a width that works well for social media
            return 1200;
        }
        return false;
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

    function getLabelForLink($link, $linkTypeMetadata) {
        global $config;

        $labelCode = '';

        if ($config) {
            if ($link['label'] === 'DEFAULT_FOR_TYPE') {
                $labelCode = $config['links']['defaultLabelsForType'][$link['type']];
                
                if ($linkTypeMetadata['countWithDefaultLabel'] > 1) {
                    $labelCode .= ' '.$linkTypeMetadata['index'];
                }
            } else {
                $labelCode = $link['label'];
            }
        }

        return $labelCode;
    }

    function formatForDisplay($inputText) {
        $outputText = nl2br($inputText);
        
        // First handle code blocks
        $outputText = preg_replace('~\[code="(.*?)"\]~', '<span class="customFormatting code">$1</span>', $outputText);
        
        // Then handle links
        $outputText = preg_replace('~\[link="(.*?)" (.*?)\]~', '<a href="$1" target="_blank" rel="noopener"><span class="customFormatting link">$2</span></a>', $outputText);
        
        // Finally handle any remaining custom formatting
        $outputText = preg_replace('~\[(.*?)="(.*?)"\]~', '<span class="customFormatting $1">$2</span>', $outputText);

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
                // Automatically tags based on certain links
                if (stringContains($link['url'], 'github.com')) {
                    array_push($tags, 'github');
                }

                if (stringContains($link['type'], 'live_site')) {
                    array_push($tags, 'live-site');
                }

                if (
                    stringContains($link['label'], 'STL') ||
                    stringContains($link['label'], '3MF') ||
                    stringContains($link['label'], 'CAD')
                ) {
                    array_push($tags, '3D');
                }

                if (stringContains($link['url'], 'noshado.ws/archive/tweets')) {
                    array_push($tags, 'archived-tweet');
                }

                if (stringContains($link['type'], 'patent')) {
                    array_push($tags, 'patent');
                }
            }
        }

        // If any video or audio files are attached to project, automatically add tags
        if(array_key_exists('screenshots', $projectFiles) && $projectFiles['screenshots']) {
            foreach($projectFiles['screenshots'] as $screenshotFileName) {
                if (stringContains($screenshotFileName, 'mov') || stringContains($screenshotFileName, 'mp4')) { 
                    array_push($tags, 'video');
                } else if (stringContains($screenshotFileName, 'mp3') || stringContains($screenshotFileName, 'm4a')) { 
                    array_push($tags, 'audio');
                } else if (stringContains(strtolower($screenshotFileName), 'youtube') && stringContains(strtolower($screenshotFileName), '.txt')) {
                    array_push($tags, 'video');
                    array_push($tags, 'youtube');
                }
            }
        }

        if(count($tags) > 0) {
            foreach($tags as $tagKey => $tagValue) {
                $tagValueFormatted = str_replace(' ', '-', strtolower($tagValue));
                $tagValueFormatted = str_replace('3d', '3D', $tagValueFormatted);
                $tags[$tagKey] = $tagValueFormatted;
            }
        }

        $tags = array_unique($tags);

        return $tags;
    }



    function convertYouTubeUrlToEmbed($url) {
        // Handle different YouTube URL formats
        $videoId = '';
        
        // Standard YouTube URL: https://www.youtube.com/watch?v=VIDEO_ID
        if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $url, $matches)) {
            $videoId = $matches[1];
        }
        // YouTube short URL: https://youtu.be/VIDEO_ID
        else if (preg_match('/youtu\.be\/([a-zA-Z0-9_-]{11})/', $url, $matches)) {
            $videoId = $matches[1];
        }
        // YouTube embed URL: https://www.youtube.com/embed/VIDEO_ID
        else if (preg_match('/youtube\.com\/embed\/([a-zA-Z0-9_-]{11})/', $url, $matches)) {
            $videoId = $matches[1];
        }

        if (!empty($videoId)) {
            return "https://www.youtube.com/embed/" . $videoId;
        }

        return false;
    }
?>