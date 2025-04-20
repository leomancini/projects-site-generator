<?php
    include('../controllers/base.php');

	$config = loadConfig();
?>
<!DOCTYPE HTML>
<html>
	<head>
		<title>Leo Mancini &ndash; Projects</title>
		<link rel="icon" type="image/png" sizes="32x32" href="resources/images/favicon-32.png">
		<link rel="icon" type="image/png" sizes="16x16" href="resources/images/favicon-16.png">
		<link rel="apple-touch-icon" sizes="180x180" href="resources/images/apple-touch-icon.png">
		<link rel="mask-icon" href="resources/images/safari-pinned-tab.svg" color="#000000">
		<link rel="manifest" href="/manifest.json">
		<link rel='stylesheet/less' href='site/resources/css/index.less<?php if ($config['debug'] === true) { echo '?hash='.rand(0, 9999); } ?>'>
		<script src='site/resources/js/lib/less.js'></script>
		<script src='site/resources/js/lib/jquery.js'></script>
		<script src='site/resources/js/index.js<?php if ($config['debug'] === true) { echo '?hash='.rand(0, 9999); } ?>'></script>
		<meta name='viewport' content='width=device-width, initial-scale=1'>
	</head>
	<body ontouchstart=''>
		<div id='projectsListContainer'>
			<form>
				<div class='inputWithCancel' id='search'>
					<input type='text' id='searchKeyword' placeholder='Search by name, year, or tags...' autocomplete='off' spellcheck='false'>
					<div class='cancel'></div>
				</div>
			</form>
			<div id='projectsListZeroSearchResults'>Sorry, no projects match that search...</div>
			<ul id='projectsList'></ul>
		</div>
	</body>
</html>