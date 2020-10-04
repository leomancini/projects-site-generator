function refreshProjectsList(params) {
    $.get('site/controllers/getProjectsList.php?' + $.param(params), function(projectsListJson) {
        projectsList = JSON.parse(projectsListJson);
        $('#projectsList').empty();
        listItemsCount = 0;

        $.each(projectsList.projects, function(key, projectInfo) {
            projectListItem = renderProjectListItem(projectInfo, params);
            $('#projectsList').append(projectListItem);
            listItemsCount++;
        });

        if (listItemsCount === 0) {
            refreshProjectsList({ 'search': '', 'zeroResults': true });
        }

        if (params.zeroResults === true) {
            $('#projectsListZeroSearchResults').addClass('visible');
        } else {
            $('#projectsListZeroSearchResults').removeClass('visible');
        }

        yearsIndex = [];
        
        setTimeout(function() {
            $('#projectsListContainer').addClass('visible');
        }, 200);
    });
}

function renderProjectListItem(projectInfo, params) {
    projectListItem = '';

    if(!yearsIndex.includes(projectInfo.manifest.startDate.timestamp.components.yearNumber)) {
        yearsIndex.push(projectInfo.manifest.startDate.timestamp.components.yearNumber);

        projectListYearHeader = `<h3>${projectInfo.manifest.startDate.timestamp.components.yearNumber}</h3>`;
        projectListItem += projectListYearHeader;
    }

    projectListItemShortDescriptionHtml = '';

    if (projectInfo.manifest.shortDescription) {
        projectListItemShortDescriptionHtml =`<span class='shortDescription'>${projectInfo.manifest.shortDescription}</span>`;
    }

    projectListItem += `<li>
        <a href='${projectInfo.directory.id}${params.search ? '('+params.search+')' : ''}'>
            <span class='name'>${projectInfo.manifest.name}</span>
            ${projectListItemShortDescriptionHtml}
            </a>
        </li>`;

    return $(projectListItem);
}

function clearLocationHash() {
    if(history.pushState) {
        history.pushState('', document.title, window.location.pathname + window.location.search);
    } else {
        location.hash = '#';
    }
}

$(document).ready(function() { 
    params = [];
    yearsIndex = [];

    setTimeout(function() {
        $('#searchKeyword').focus();
    }, 200);

    if (location.hash === '') {
        refreshProjectsList(params);
    } else {
        urlKeyword = decodeURIComponent(location.hash.replace('#', ''));

        $('#searchKeyword').val(urlKeyword);

        refreshProjectsList({ 'search': urlKeyword });

        $('#searchKeyword').parent('.inputWithCancel').children('.cancel').addClass('visible');
    }

    $('#searchKeyword').on('keyup', function() {
        keyword = $(this).val();

        if (keyword === '') {
			clearLocationHash();
            
            $(this).parent('.inputWithCancel').children('.cancel').removeClass('visible');
        } else {
            location.hash = keyword;

            $(this).parent('.inputWithCancel').children('.cancel').addClass('visible');
        } 
        
        refreshProjectsList({ 'search': keyword });
    });

    $('.inputWithCancel .cancel').click(function() {
        input = $(this).parent().children('input');

        input.focus().val('');

        $(this).removeClass('visible');
    });

    $('.inputWithCancel#search .cancel').click(function() {
        refreshProjectsList({ 'search': '' });
        clearLocationHash();
    });
});