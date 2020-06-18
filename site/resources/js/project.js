// $('#back').click(function() {
//     if (window.location.hostname === 'localhost') {
//         if (document.referrer.includes('localhost')) {
//             window.history.back();
//         } else {
//             window.location = 'http://localhost/project-directory-viewer/';
//         }
//     } else if (window.location.hostname.includes('leomancini.net')) {
//         if (document.referrer === 'leomancini.net') {
//             window.history.back();
//         } else {
//             window.location = 'https://leomancini.net/';
//         }
//     }
// });

function calculateOffsetForScreenshotsHasMacDesktopShadow() {
    windowWidth = $(window).width();
    scaleFactor = 12.5;

    if ((windowWidth/scaleFactor) < 108) {
        $('.hasMacDesktopShadow').each(function() {
            $(this).css('margin-left', (windowWidth/scaleFactor) * -1).css('max-width', `calc(100% + ${windowWidth/(scaleFactor/2)}px)`);
        });
    }
}

$(document).ready(function() { 
    calculateOffsetForScreenshotsHasMacDesktopShadow();
});

$(window).resize(function() {
    calculateOffsetForScreenshotsHasMacDesktopShadow();
});