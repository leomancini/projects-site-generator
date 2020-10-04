<?php
    include('../controllers/base.php');
    include('../controllers/getProject.php');

    $config = loadConfig();

    // If the previous page is the project index after a search has been run
    if (strpos($_GET['directoryId'], '(') !== false) {
        $urlComponents = explode('(', $_GET['directoryId']);
        $projectDirectoryId = $urlComponents[0];
        $searchTerm = explode(')', $urlComponents[1])[0];
    } else {
        $projectDirectoryId = $_GET['directoryId'];
    }
            
    $projectManifest = getProjectManifest($projectDirectoryId);
    $projectFiles = getProjectFiles($projectDirectoryId);

    function getIconForLink($link) {
        global $config;

        $iconCode = '';

        if ($link['icon'] == 'DEFAULT_FOR_TYPE') {
            $iconCode = $config['links']['defaultIconsForType'][$link['type']];
        } else {
            $iconCode = $link['icon'];
        }

        return $iconCode;
    }

    function getLabelForLink($link) {
        global $config;

        $labelCode = '';

        if ($link['label'] == 'DEFAULT_FOR_TYPE') {
            $labelCode = $config['links']['defaultLabelsForType'][$link['type']];
        } else {
            $labelCode = $link['label'];
        }

        return $labelCode;
    }
?>
<!DOCTYPE HTML>
<html>
	<head>
		<title>Leo Mancini &ndash; <?php echo $projectManifest['name']; ?></title>
		<link rel='stylesheet/less' href='site/resources/css/project.less?hash=<?php echo rand(0, 9999); ?>'>
		<script src='site/resources/js/lib/less.js'></script>
		<script src='site/resources/js/lib/jquery.js'></script>
        <script src='site/resources/js/project.js?hash=<?php echo rand(0, 9999); ?>'></script>
        <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
		<meta name='viewport' content='width=device-width, initial-scale=1'>
	</head>
	<body ontouchstart=''>
		<div id='projectInfoContainer'>
            <a id='back' href='./<?php if(isset($searchTerm)) { echo '#'.$searchTerm; } ?>'>← &nbsp;back to projects list</a>
            <h1><?php echo $projectManifest['name']; ?></h1>

            <div id='descriptions'>
                <?php if($projectManifest['shortDescription']) { ?>
                    <div id='shortDescription'><?php echo $projectManifest['shortDescription']; ?></div>
                <?php } ?>
                <?php
                    $longDescription = getProjectLongDescription($projectDirectoryId);
                    if($longDescription) {
                ?>
                    <div id='longDescription'><?php echo formatForDisplay($longDescription); ?></div>
                <?php } ?>
            </div>
            <div id='credits'>
                <?php if(count($projectManifest['credits']) > 0) { ?>
                    <label>Credits:</label>
                    <?php foreach($projectManifest['credits'] as $credit) { ?>
                        <?php if($credit['link']) { ?>
                            <a href='<?php echo $credit['link']; ?>' target='_blank' rel='noopener' class='credit'><?php echo $credit['name']; if($credit['type']) { echo " (".$credit['type'].")"; } ?></a>
                        <?php } else { ?>
                            <span class='credit'><?php echo $credit['name']; if($credit['type']) { echo " (".$credit['type'].")"; } ?></span>
                        <?php } ?>
                    <?php } ?>
                <?php } ?>
            </div>
            <div id='tags'>
                <?php if(count($projectManifest['tags']) > 0) { ?>
                    <?php foreach($projectManifest['tags'] as $tag) { ?>
                        <a href='./#<?php echo $tag; ?>' class='tag'>#<?php echo $tag; ?></a>
                    <?php } ?>
                <?php } ?>
            </div>
            <div id='links'>
                <?php if(count($projectManifest['links']) > 0) { ?>
                    <?php foreach($projectManifest['links'] as $link) { ?><a href='<?php echo $link['url']; ?>' target='_blank' rel='noopener' class='link'>
                            <span class='icon'><i class="material-icons"><?php echo getIconForLink($link); ?></i></span>
                            <span class='label'><?php echo getLabelForLink($link); ?></span>
                        </a><?php } ?>
                <?php } ?>
            </div>
            <div id='screenshots'>
                <?php
                    if($projectFiles['screenshots']) {
                        foreach($projectFiles['screenshots'] as $screenshotFileName) {
                            $imageClasses = [];

                            if(strpos($screenshotFileName, '@2x')) { array_push($imageClasses, 'retina'); }
                            if(strpos($screenshotFileName, 'hasMacDesktopShadow')) { array_push($imageClasses, 'hasMacDesktopShadow'); }

                            $imageClassesString = implode(' ', $imageClasses);

                            if ($screenshotFileName !== 'hidden') {
                                if(strpos(strtolower($screenshotFileName), '.png') || strpos(strtolower($screenshotFileName), '.jpg') || strpos(strtolower($screenshotFileName), '.gif')) {
                                    echo "<img src='projects/$projectDirectoryId/screenshots/$screenshotFileName' class='$imageClassesString'>";
                                } else if(strpos(strtolower($screenshotFileName), '.mov') || strpos(strtolower($screenshotFileName), '.mp4')) {
                                    echo "<video class='$imageClassesString' loop autoplay muted playsinline><source src='projects/$projectDirectoryId/screenshots/$screenshotFileName'></video>";
                                } else {
                                    echo 'UNSUPPORTED_FILE_TYPE';
                                }
                            }
                        }
                    }
                ?>
            </div>
        </div>
	</body>
</html>