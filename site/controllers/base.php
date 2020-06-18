<?php
    function loadConfig() {
        $configFile = file_get_contents('../../config/config.json');
        
        return json_decode($configFile, true);
    }
?>