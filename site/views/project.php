<?php
    include('../controllers/base.php');
    include('../controllers/getProject.php');

    $config = loadConfig();

    $projectDirectoryId = $_GET['directoryId'];            
    $projectManifest = getProjectManifest($projectDirectoryId);
    $projectFiles = getProjectFiles($projectDirectoryId);
    $tags = getProjectTags($projectManifest, $projectFiles);
    $shareImage = getProjectShareImage(projectDirectoryId, $projectFiles);
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
        <?php if($shareImage) { ?>
            <meta property='og:image' content='<?php echo $shareImage; ?>'>
        <?php } ?>
        <meta name='format-detection' content='telephone=no'>
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
                        <?php
                            $projectLinkTypesMetadata = [];
                            foreach($projectManifest['links'] as $link) {
                                $projectLinkTypesMetadata[$link['type']]['count']++;

                                if($link['label'] === 'DEFAULT_FOR_TYPE') {
                                    $projectLinkTypesMetadata[$link['type']]['countWithDefaultLabel']++;
                                }
                            }
                        ?>
                        <?php foreach($projectManifest['links'] as $link) { ?>
                            <a href='<?php echo $link['url']; ?>' target='_blank' rel='noopener' class='link'>
                                <?php $projectLinkTypesMetadata[$link['type']]['index']++; ?>
                                <span class='icon'><i class="material-icons"><?php echo getIconForLink($link); ?></i></span>
                                <span class='label'><?php echo getLabelForLink($link, $projectLinkTypesMetadata[$link['type']]); ?></span>
                            </a>
                        <?php } ?>
                    <?php } ?>
                <?php } ?>
            </div>
            <div id='screenshots'>
                <?php
                    if($projectFiles['screenshots']) {
                        foreach($projectFiles['screenshots'] as $screenshotFileName) {
                            $imageClasses = [];

                            if(stringContains($screenshotFileName, '@2x')) { array_push($imageClasses, 'retina'); }
                            if(stringContains($screenshotFileName, 'hasMacDesktopShadow')) { array_push($imageClasses, 'hasMacDesktopShadow'); }

                            $imageClassesString = implode(' ', $imageClasses);

                            if ($screenshotFileName !== 'hidden') {
                                if(stringContains(strtolower($screenshotFileName), '.png') || stringContains(strtolower($screenshotFileName), '.jpg') || stringContains(strtolower($screenshotFileName), '.gif')) {
                                    echo "<img src='projects/$projectDirectoryId/screenshots/$screenshotFileName' class='$imageClassesString'>";
                                } else if(stringContains(strtolower($screenshotFileName), '.mov') || stringContains(strtolower($screenshotFileName), '.mp4')) {
                                    if (stringContains(strtolower($screenshotFileName), '--play-audio')) {
                                        echo "<video class='$imageClassesString' playsinline controls><source src='projects/$projectDirectoryId/screenshots/$screenshotFileName'></video>";
                                    } else {
                                        echo "<video class='$imageClassesString' loop autoplay muted playsinline><source src='projects/$projectDirectoryId/screenshots/$screenshotFileName'></video>";
                                    }
                                } else if(stringContains(strtolower($screenshotFileName), '.mp3') || stringContains(strtolower($screenshotFileName), '.m4a')) {
                                    echo "<audio class='$imageClassesString' controls><source src='projects/$projectDirectoryId/screenshots/$screenshotFileName' type='audio/mpeg'></audio>";
                                } else if(stringContains(strtolower($screenshotFileName), '.txt')) {
                                    $file = __DIR__ . "/../../projects/$projectDirectoryId/screenshots/$screenshotFileName";
                                    $fileContents = file_get_contents($file);
                                    $fileContentsFormatted = str_replace("\n", "<br>", $fileContents);
                                    $fileContentsFormatted = str_replace("<?php", "<&quest;php", $fileContentsFormatted);
                                    $fileContentsFormatted = str_replace("  ", "&nbsp;&nbsp;", $fileContentsFormatted);

                                    $fileNameWithoutExtension = explode(".", $screenshotFileName)[0];
                                    $fileStyle = explode("--", $fileNameWithoutExtension)[1];

                                    echo "<div class='text";
                                    if ($fileStyle) { echo " ".$fileStyle; }
                                    echo "'>";
                                    echo $fileContentsFormatted;
                                    echo "</div>";
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