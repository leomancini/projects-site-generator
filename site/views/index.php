<!DOCTYPE HTML>
<html>
	<head>
		<title>Leo Mancini &ndash; Projects</title>
		<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter">
		<link rel='stylesheet/less' href='site/resources/css/index.less?hash=<?php echo rand(0, 9999); ?>'>
		<script src='site/resources/js/lib/less.js'></script>
		<script src='site/resources/js/lib/jquery.js'></script>
		<script src='site/resources/js/index.js?hash=<?php echo rand(0, 9999); ?>'></script>
		<meta name='viewport' content='width=device-width, initial-scale=1'>
	</head>
	<body ontouchstart=''>
		<div id='projectsListContainer'>
			<form>
				<div class='inputWithCancel' id='search'>
					<input type='text' id='searchKeyword' placeholder='Search by name, year, or tags...' autocomplete='off'>
					<div class='cancel'></div>
				</div>
			</form>
			<div id='projectsListZeroSearchResults'>Sorry, no projects match that search...</div>
			<ul id='projectsList'></ul>
		</div>
	</body>
</html>