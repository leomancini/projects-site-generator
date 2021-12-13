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
?>