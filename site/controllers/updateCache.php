<?php
    // Apache's PHP inherits an en_US.UTF-8 collation, which sorts directory
    // listings case-insensitively. The CLI defaults to byte order, so set the
    // locale explicitly to keep screenshot/download ordering identical.
    setlocale(LC_COLLATE, 'en_US.UTF-8');

    include('getProjectsList.php');

    $projectsList = getProjectsList();
    $projectsListWithManifests = getProjectsListWithManifestsAndFiles($projectsList);

	$cacheFile = '..'.$config['cacheFile'];
	$cacheFileHandler = fopen($cacheFile, 'w') or die('Unable to open file!');
	$cacheData = json_encode($projectsListWithManifests, JSON_PRETTY_PRINT);;
	fwrite($cacheFileHandler, $cacheData);
	fclose($cacheFileHandler);
 ?>