
<?php
use yii\bootstrap5\Tabs;
use yii\helpers\Html;

$js = <<<JS
    $(document).on("click", "#editBtn", function() {
        $(".editable").each(function() {
            $(this).find(".title-view, .desc-view").hide();
            $(this).find(".title-edit, .desc-edit").show();
        });
        
        $(this).after('<button id="saveBtn" class="btn btn-primary mt-3">Сохранить</button>  '); // Add save button
        $(this).after('<button id="cancelBtn" class="btn btn-secondary mt-3 ml-2">Отмена</button>  '); // Add cancel button
        $(this).remove();
    });

    $(document).on("click", "#saveBtn", function() {
        let dataToSend = {};
        
        $.ajax({
            url: "/path/to/save", // TODO: замените на правильный URL
            method: "POST",
            data: dataToSend,
            success: function(response) {
                // TODO: обработка успешного ответа
            },
            error: function() {
                alert("Произошла ошибка при сохранении.");
            }
        });
    });

    $(document).on("click", "#cancelBtn", function() {
        $(".editable").each(function() {
            $(this).find(".title-edit, .desc-edit").hide();
            $(this).find(".title-view, .desc-view").show();
        });
        $("#saveBtn").remove();
        $(this).remove();
        $("#editBtnContainer").append('<button id="editBtn" class="btn btn-secondary mt-3">Редактировать</button>');
    });

JS;

$this->registerJs($js);

$localizations = $model->localizations;
$tabs = [];

foreach ($localizations as $lang => $data) {
    $description = nl2br($data['description']);
    $title = nl2br($data['title']);

    $content = <<<HTML
    <div class="editable" data-lang="$lang">
        <strong>Title:</strong> <span class="title-view">$title</span>
        <input class="title-edit form-control mt-2" type="text" value="{$data['title']}" style="display:none;">
        <br><strong>Description:</strong> <span class="desc-view">$description</span>
        <textarea rows="30" class="desc-edit form-control mt-2" style="display:none;">{$data['description']}</textarea>
    </div>
HTML;

    $tabs[] = [
        'label' => $lang,
        'content' => $content,
    ];
}
echo '</br>';
echo Tabs::widget(['items' => $tabs]);
?>

<div id="editBtnContainer"><button id="editBtn" class="btn btn-secondary mt-3">Редактировать</button></div>
