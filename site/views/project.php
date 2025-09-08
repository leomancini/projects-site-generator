<?php
    include('../controllers/base.php');
    include('../controllers/getProject.php');

    $config = loadConfig();

    $projectDirectoryId = $_GET['directoryId'];            
    $projectManifest = getProjectManifest($projectDirectoryId);
    $projectFiles = getProjectFiles($projectDirectoryId);
    $tags = getProjectTags($projectManifest, $projectFiles);
    
    $shareImage = getProjectShareImage($projectDirectoryId, $projectFiles);
?>
<!DOCTYPE HTML>
<html>
	<head>
        <title>Leo Mancini &ndash; <?php echo htmlspecialchars($projectManifest['name'], ENT_QUOTES, 'UTF-8'); ?></title>
        <link rel="icon" type="image/png" sizes="32x32" href="site/resources/images/favicon-32-light.png?v=<?php echo $iconVersion; ?>" media="(prefers-color-scheme: light)">
		<link rel="icon" type="image/png" sizes="32x32" href="site/resources/images/favicon-32-dark.png?v=<?php echo $iconVersion; ?>" media="(prefers-color-scheme: dark)">
		<link rel="icon" type="image/png" sizes="16x16" href="site/resources/images/favicon-16-light.png?v=<?php echo $iconVersion; ?>" media="(prefers-color-scheme: light)">
		<link rel="icon" type="image/png" sizes="16x16" href="site/resources/images/favicon-16-dark.png?v=<?php echo $iconVersion; ?>" media="(prefers-color-scheme: dark)">
		<link rel="apple-touch-icon" sizes="180x180" href="site/resources/images/apple-touch-icon.png?v=<?php echo $iconVersion; ?>">
		<link rel="mask-icon" href="site/resources/images/safari-pinned-tab.svg?v=<?php echo $iconVersion; ?>" color="#000000">
		<link rel="manifest" href="site/manifest.json">
        <link rel='stylesheet/less' href='site/resources/css/project.less<?php if ($config['debug'] === true) { echo '?hash='.rand(0, 9999); } ?>'>
        <script src='site/resources/js/lib/less.js'></script>
        <script src='site/resources/js/lib/jquery.js'></script>
        <script src='site/resources/js/project.js<?php if ($config['debug'] === true) { echo '?hash='.rand(0, 9999); } ?>'></script>
        <link href='https://fonts.googleapis.com/icon?family=Material+Icons' rel='stylesheet'>
        <meta name='viewport' content='width=device-width, initial-scale=1'>
        <meta property='og:url' content='https://leomancini.net/<?php echo $projectDirectoryId; ?>'>
        <meta property='og:type' content='website'>
        <meta property='og:title' content='<?php echo htmlspecialchars($projectManifest['name'], ENT_QUOTES, 'UTF-8'); ?>'>
        <meta property='og:description' content='<?php echo htmlspecialchars($projectManifest['shortDescription'], ENT_QUOTES, 'UTF-8'); ?>'>
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
            <h1><?php echo htmlspecialchars($projectManifest['name'], ENT_QUOTES, 'UTF-8'); ?></h1>
            <div id='descriptions'>
                <?php if($projectManifest['shortDescription']) { ?>
                    <div id='shortDescription'><?php echo htmlspecialchars($projectManifest['shortDescription'], ENT_QUOTES, 'UTF-8'); ?></div>
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
                                <a href='<?php echo htmlspecialchars($credit['link'], ENT_QUOTES, 'UTF-8'); ?>' target='_blank' rel='noopener' class='credit'><?php echo htmlspecialchars($credit['name'], ENT_QUOTES, 'UTF-8'); if($credit['type']) { echo " (".htmlspecialchars($credit['type'], ENT_QUOTES, 'UTF-8').")"; } ?></a>
                            <?php } else { ?>
                                <span class='credit'><?php echo htmlspecialchars($credit['name'], ENT_QUOTES, 'UTF-8'); if($credit['type']) { echo " (".htmlspecialchars($credit['type'], ENT_QUOTES, 'UTF-8').")"; } ?></span>
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
                            if(stringContains($screenshotFileName, '--size=')) {
                                $sizeString = explode('--size=', $screenshotFileName)[1];
                                $sizeString = explode('.', $sizeString)[0];
                                
                                if (strpos($sizeString, 'x') !== false) {
                                    list($width, $height) = explode('x', $sizeString);
                                    $sizeOverride = "width: {$width}px; height: {$height}px;";
                                } else {
                                    $width = $sizeString;
                                    $sizeOverride = "width: {$width}px;";
                                }
                            }

                            $imageClassesString = implode(' ', $imageClasses);

                            if ($screenshotFileName !== 'hidden') {
                                if(stringContains(strtolower($screenshotFileName), '.png') || stringContains(strtolower($screenshotFileName), '.jpg') || stringContains(strtolower($screenshotFileName), '.jpeg') || stringContains(strtolower($screenshotFileName), '.gif')) {
                                    echo "<img src='projects/$projectDirectoryId/screenshots/$screenshotFileName' class='$imageClassesString' style='$sizeOverride'>";
                                } else if(stringContains(strtolower($screenshotFileName), '.mov') || stringContains(strtolower($screenshotFileName), '.mp4')) {
                                    if (stringContains(strtolower($screenshotFileName), '--play-audio')) {
                                        echo "<video class='$imageClassesString' style='$sizeOverride' playsinline controls><source src='projects/$projectDirectoryId/screenshots/$screenshotFileName'></video>";
                                    } else {
                                        echo "<video class='$imageClassesString' style='$sizeOverride' loop autoplay muted playsinline><source src='projects/$projectDirectoryId/screenshots/$screenshotFileName'></video>";
                                    }
                                } else if(stringContains(strtolower($screenshotFileName), '.mp3') || stringContains(strtolower($screenshotFileName), '.m4a')) {
                                    echo "<audio class='$imageClassesString' controls><source src='projects/$projectDirectoryId/screenshots/$screenshotFileName' type='audio/mpeg'></audio>";
                                } else if(stringContains(strtolower($screenshotFileName), '.txt')) {
                                    $file = __DIR__ . "/../../projects/$projectDirectoryId/screenshots/$screenshotFileName";
                                    $fileContents = file_get_contents($file);
                                    
                                    // Check if this is a YouTube video file
                                    if (stringContains(strtolower($screenshotFileName), 'youtube')) {
                                        $youtubeUrl = trim($fileContents);
                                        $embedUrl = convertYouTubeUrlToEmbed($youtubeUrl);
                                        if ($embedUrl) {
                                            // Check for aspect ratio override in filename (e.g., youtube-1--aspect=9x16.txt)
                                            $aspectRatioStyle = '';
                                            if (stringContains($screenshotFileName, '--aspect=')) {
                                                $aspectString = explode('--aspect=', $screenshotFileName)[1];
                                                $aspectString = explode('.', $aspectString)[0];
                                                
                                                if (strpos($aspectString, 'x') !== false) {
                                                    list($aspectWidth, $aspectHeight) = explode('x', $aspectString);
                                                    $paddingBottom = ($aspectHeight / $aspectWidth) * 100;
                                                    $aspectRatioStyle = "padding-bottom: {$paddingBottom}%; height: 0;";
                                                }
                                            }
                                            
                                            $styleContent = trim($sizeOverride . ' ' . $aspectRatioStyle);
                                            echo "<div class='youtubeVideo" . ($imageClassesString ? " $imageClassesString" : "") . "'" . ($styleContent ? " style='$styleContent'" : "") . ">";
                                            echo "<iframe src='$embedUrl' frameborder='0' allow='accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture' allowfullscreen></iframe>";
                                            echo "</div>";
                                        }
                                    } else {
                                        // Handle regular text files
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
                                    }
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