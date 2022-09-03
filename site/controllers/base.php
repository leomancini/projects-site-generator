<?php
    function loadConfig() {
        $configFile = file_get_contents('../../config/config.json');
        
        return json_decode($configFile, true);
    }

    function stringContains($haystack, $needle) {
        if (strpos($haystack, $needle) !== false) {
            return true;
        } else {
            return false;
        }
    }
?>