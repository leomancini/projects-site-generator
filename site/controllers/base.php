<?php
    function loadConfig() {
        $configFile = file_get_contents('../../config/config.json');
        
        return json_decode($configFile, true);
    }

    function stringContains($haystack, $needle) {
        if (stringContains($haystack, $needle)) {
            return true;
        } else {
            return false;
        }
    }
?>