function calculateOffsetForScreenshotsHasMacDesktopShadow() {
    windowWidth = $(window).width();
    scaleFactor = 12.5;

    if ((windowWidth/scaleFactor) < 108) {
        $('.hasMacDesktopShadow').each(function() {
            $(this).css('margin-left', (windowWidth/scaleFactor) * -1).css('max-width', `calc(100% + ${windowWidth/(scaleFactor/2)}px)`);
        });
    }
}

function goBackToProjectsList() {
    if (window.history.length > 1) {
        window.history.go(-1);
    } else {
        window.close();
    }

    return false;
}

$(document).ready(function() { 
    calculateOffsetForScreenshotsHasMacDesktopShadow();
});

$(window).resize(function() {
    calculateOffsetForScreenshotsHasMacDesktopShadow();
});