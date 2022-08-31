<?php
    include('../controllers/base.php');
    include('../controllers/getProject.php');

    $config = loadConfig();

    $projectDirectoryId = $_GET['directoryId'];            
    $projectManifest = getProjectManifest($projectDirectoryId);
    $projectFiles = getProjectFiles($projectDirectoryId);
    $tags = getProjectTags($projectManifest);
?>
<!DOCTYPE HTML>
<html>
	<head>
		<title>Leo Mancini &ndash; <?php echo $projectManifest['name']; ?></title>
		<link rel='stylesheet/less' href='site/resources/css/project.less<?php if ($config['debug'] === true) { echo '?hash='.rand(0, 9999); } ?>'>
		<script src='site/resources/js/lib/less.js'></script>
		<script src='site/resources/js/lib/jquery.js'></script>
        <script src='site/resources/js/project.js<?php if ($config['debug'] === true) { echo '?hash='.rand(0, 9999); } ?>'></script>
        <link href='https://fonts.googleapis.com/icon?family=Material+Icons' rel='stylesheet'>
		<meta name='viewport' content='width=device-width, initial-scale=1'>
        <meta property='og:url' content='https://leomancini.net/<?php echo $projectDirectoryId; ?>'>
        <meta property='og:type' content='website'>
        <meta property='og:title' content='<?php echo $projectManifest['name']; ?>'>
        <meta property='og:description' content='<?php echo $projectManifest['shortDescription']; ?>'>
        <?php if($projectFiles['screenshots'] && reset($projectFiles['screenshots'])) { ?>
            <meta property='og:image' content='https://leomancini.net/projects/<?php echo $projectDirectoryId; ?>/screenshots/<?php echo reset($projectFiles['screenshots']); ?>'>
        <?php } ?>
    </head>
	<body ontouchstart=''>
		<div id='projectInfoContainer'>
            <?php if (
                $_SERVER['HTTP_REFERER'] === 'http://localhost/projects-site-generator/' ||
                $_SERVER['HTTP_REFERER'] === 'https://leomancini.net/' ||
                $_SERVER['HTTP_REFERER'] === 'https://www.leomancini.net/' ||
                $_SERVER['HTTP_REFERER'] === 'https://projects.leo.gd/' ||
                $_SERVER['HTTP_REFERER'] === 'https://www.projects.leo.gd/'
            ) { ?>
            <a id='back' onclick='goBackToProjectsList();'>← &nbsp;back</a>
            <?php } else { ?>
            <a id='back' href='./'>← &nbsp;back to projects list</a>
            <?php } ?>
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
                <?php if(array_key_exists('credits', $projectManifest)) { ?>
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
                <?php } ?>
            </div>
            <div id='tags'>
                <?php if(count($tags) > 0) { ?>
                    <?php foreach($tags as $tag) { ?>
                        <a href='./##<?php echo $tag; ?>' class='tag'>#<?php echo $tag; ?></a>
                    <?php } ?>
                <?php } ?>
            </div>
            <div id='links'>
                <?php if(array_key_exists('links', $projectManifest)) { ?>
                    <?php if(count($projectManifest['links']) > 0) { ?>
                        <?php foreach($projectManifest['links'] as $link) { ?><a href='<?php echo $link['url']; ?>' target='_blank' rel='noopener' class='link'>
                                <span class='icon'><i class="material-icons"><?php echo getIconForLink($link); ?></i></span>
                                <span class='label'><?php echo getLabelForLink($link); ?></span>
                            </a><?php } ?>
                    <?php } ?>
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
                                    if (strpos(strtolower($screenshotFileName), '--play-audio')) {
                                        echo "<video class='$imageClassesString' playsinline controls><source src='projects/$projectDirectoryId/screenshots/$screenshotFileName'></video>";
                                    } else {
                                        echo "<video class='$imageClassesString' loop autoplay muted playsinline><source src='projects/$projectDirectoryId/screenshots/$screenshotFileName'></video>";
                                    }
                                } else if(strpos(strtolower($screenshotFileName), '.mp3') || strpos(strtolower($screenshotFileName), '.m4a')) {
                                    echo "<audio class='$imageClassesString' controls><source src='projects/$projectDirectoryId/screenshots/$screenshotFileName' type='audio/mpeg'></audio>";
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