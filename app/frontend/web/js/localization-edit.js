$(document).on("click", "#editBtn", function() {
    var saveText = $(this).data("save");
    var cancelText = $(this).data("cancel");

    $(".editable").each(function() {
        $(this).find(".title-view, .desc-view").hide();
        $(this).find(".title-edit, .desc-edit").show();
    });

    $(this).after('<button id="saveBtn" class="btn btn-primary m-1">' + saveText + '</button>');
    $(this).after('<button id="cancelBtn" class="btn btn-secondary m-1">' + cancelText + '</button>');
    $(this).hide();
});

$(document).on("click", "#saveBtn", function() {
    let dataToSend = {};
    $(".editable").each(function() {
        var lang = $(this).data("lang");
        dataToSend[lang] = {
            title: $(this).find(".title-edit").val(),
            description: $(this).find(".desc-edit").val()
        };
    });

    $.ajax({
        url: "/path/to/save", // Замените на ваш URL
        method: "POST",
        data: dataToSend,
        success: function(response) {
            // Обработка успешного ответа
        },
        error: function() {
            alert("Error was occurred");
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
