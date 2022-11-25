<?php
    include('getProjectsList.php');

    $projectsList = getProjectsList();
    $projectsListWithManifests = getProjectsListWithManifestsAndFiles($projectsList);

	$cacheFile = '..'.$config['cacheFile'];
	$cacheFileHandler = fopen($cacheFile, 'w') or die('Unable to open file!');
	$cacheData = json_encode($projectsListWithManifests, JSON_PRETTY_PRINT);;
	fwrite($cacheFileHandler, $cacheData);
	fclose($cacheFileHandler);
 ?>