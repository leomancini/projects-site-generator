function refreshProjectsList(params) {
  $.get(
    "site/controllers/getProjectsList.php?" + $.param(params),
    function (projectsListJson) {
      projectsList = JSON.parse(projectsListJson);
      $("#projectsList").empty();
      listItemsCount = 0;

      let displayType = "list";

      $("#projectsList").addClass(displayType);

      $.each(projectsList.projects, function (key, projectInfo) {
        projectListItem = renderProjectListItem(
          projectInfo,
          params,
          displayType
        );
        $("#projectsList").append(projectListItem);
        listItemsCount++;
      });

      if (listItemsCount === 0) {
        refreshProjectsList({ search: "", zeroResults: true });
      }

      if (params.zeroResults === true) {
        $("#projectsListZeroSearchResults").addClass("visible");
      } else {
        $("#projectsListZeroSearchResults").removeClass("visible");
      }

      yearsIndex = [];

      setTimeout(function () {
        $("#projectsListContainer").addClass("visible");
      }, 200);
    }
  );
}

function renderProjectListItem(projectInfo, params, displayType) {
  projectListItem = "";

  if (
    !yearsIndex.includes(
      projectInfo.manifest.startDate.timestamp.components.yearNumber
    )
  ) {
    yearsIndex.push(
      projectInfo.manifest.startDate.timestamp.components.yearNumber
    );

    projectListYearHeader = `<h3>${projectInfo.manifest.startDate.timestamp.components.yearNumber}</h3>`;
    projectListItem += projectListYearHeader;
  }

  projectListItemShortDescriptionHtml = "";

  if (projectInfo.manifest.shortDescription) {
    projectListItemShortDescriptionHtml = `<span class='shortDescription'>${projectInfo.manifest.shortDescription}</span>`;
  }

  if (displayType === "list") {
    projectListItem += `<li>
            <a href='${projectInfo.directory.id}'>
                <span class='name'>${projectInfo.manifest.name}</span>
                ${projectListItemShortDescriptionHtml}
                </a>
            </li>`;
  } else if (displayType === "grid") {
    projectListItemThumbnail = "";

    if (projectInfo.files.screenshots) {
      let firstScreenshot =
        projectInfo.files.screenshots[
          Object.keys(projectInfo.files.screenshots)[0]
        ];

      if (
        firstScreenshot &&
        (firstScreenshot.includes(".jpg") || firstScreenshot.includes(".png"))
      ) {
        projectListItemThumbnail = `<div class='thumbnail' style='background-image: url("projects/${projectInfo.directory.id}/screenshots/${firstScreenshot}");'></div>`;
      } else {
        projectListItemThumbnail = `<div class='thumbnail'></div>`;
      }
    } else {
      projectListItemThumbnail = `<div class='thumbnail'></div>`;
    }

    projectListItem += `<li>
            <a href='${projectInfo.directory.id}'>
                ${projectListItemThumbnail}
                <span class='name'>${projectInfo.manifest.name}</span>
                ${projectListItemShortDescriptionHtml}
                </a>
            </li>`;
  }

  return $(projectListItem);
}

function clearLocationHash() {
  if (history.pushState) {
    history.pushState(
      "",
      document.title,
      window.location.pathname + window.location.search
    );
  } else {
    location.hash = "#";
  }
}

$(document).ready(function () {
  params = [];
  yearsIndex = [];

  setTimeout(function () {
    $("#searchKeyword").focus();
  }, 200);

  if (location.hash === "") {
    refreshProjectsList(params);
  } else {
    if (location.hash.charAt(0) === "#" && location.hash.charAt(1) === "#") {
      urlKeyword = location.hash.replaceAll("##", "#");
    } else {
      urlKeyword = location.hash.replaceAll("#", "");
    }

    urlKeyword = urlKeyword.replaceAll("%23", "#");
    urlKeyword = decodeURI(urlKeyword);

    $("#searchKeyword").val(urlKeyword);

    refreshProjectsList({ search: urlKeyword });

    $("#searchKeyword")
      .parent(".inputWithCancel")
      .children(".cancel")
      .addClass("visible");
  }

  $("form").submit(function (e) {
    e.preventDefault();
  });

  var debounce = null;

  $("#searchKeyword").on("keyup", function (e) {
    keyword = $(this).val();

    e.preventDefault();

    const keysToIgnore = ["Shift", "Meta", "Control", "Alt", "Enter", "#"];

    if (!keysToIgnore.includes(e.key)) {
      if (keyword === "" || keyword === "#") {
        $(this)
          .parent(".inputWithCancel")
          .children(".cancel")
          .removeClass("visible");
      } else {
        $(this)
          .parent(".inputWithCancel")
          .children(".cancel")
          .addClass("visible");
      }
    }

    clearTimeout(debounce);

    debounce = setTimeout(function () {
      if (!keysToIgnore.includes(e.key)) {
        if (keyword === "" || keyword === "#") {
          clearLocationHash();

          $(this)
            .parent(".inputWithCancel")
            .children(".cancel")
            .removeClass("visible");

          refreshProjectsList({ search: "" });
        } else {
          if (keyword.charAt(0) === "#") {
            location.hash = `##${keyword.replaceAll("#", "")}`;
          } else {
            location.hash = keyword;
          }

          $(this)
            .parent(".inputWithCancel")
            .children(".cancel")
            .addClass("visible");

          refreshProjectsList({ search: keyword });
        }
      }
    }, 200);
  });

  $(".inputWithCancel .cancel").click(function () {
    input = $(this).parent().children("input");

    input.focus().val("");

    $(this).removeClass("visible");
  });

  $(".inputWithCancel#search .cancel").click(function () {
    refreshProjectsList({ search: "" });
    clearLocationHash();
  });
});
