$(document).on("click", "#editBtn", function() {
    let saveText = $(this).data("save");
    let cancelText = $(this).data("cancel");
    let url = $(this).data("save-url");

    $(".editable").each(function() {
        $(this).find(".title-view, .desc-view").hide();
        $(this).find(".title-edit, .desc-edit").show();
    });

    $(this).after(`<button id="saveBtn" class="btn btn-primary m-1" data-url="${url}">${saveText}</button>`);
    $(this).after(`<button id="cancelBtn" class="btn btn-secondary m-1">${cancelText}</button>`);
    $(this).hide();
});

$(document).on("click", "#saveBtn", function() {
    let dataToSend = {localizations: {}};
    let url = $(this).data('url');

    $(".editable").each(function() {
        var lang = $(this).data("lang");
        dataToSend.localizations[lang] = {
            title: $(this).find(".title-edit").val(),
            description: $(this).find(".desc-edit").val()
        };
    });

    $.ajax({
        url: url,
        method: "POST",
        data: dataToSend,
        success: function(response) {
            location.reload();
        },
        error: function(xhr, status, error) {
            console.log("Error status: " + status);
            console.log("Error message: " + error);
            console.log("Error response: " + xhr.responseText);
        }
    });
});

$(document).on("click", "#cancelBtn", function() {
    var editText = $("#editBtn").data("edit");
    $("#editBtn").show();
    $(".editable").each(function() {
        var $this = $(this);
        $this.find(".title-edit, .desc-edit").hide();
        $this.find(".title-view, .desc-view").show();
    });
    $("#saveBtn, #cancelBtn").remove();
});
