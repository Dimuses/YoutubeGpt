<?php

use frontend\assets\LocalizationEditAsset;
use yii\bootstrap5\Tabs;
use yii\helpers\Html;
use yii\helpers\Url;

LocalizationEditAsset::register($this);


$localizations = $model?->localizations;
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

$editButtonText = Yii::t('app', 'Update');
$saveButtonText = Yii::t('app', 'Save');
$cancelButtonText = Yii::t('app', 'Cancel');

echo
"<div id='editBtnContainer'>
    <button id='editBtn'
            class='btn btn-secondary mt-3'
            data-edit='$editButtonText'
            data-save='$saveButtonText'
            data-cancel='$cancelButtonText'
            data-save-url='" . Url::to(['/video/update-localization', 'videoId' => $videoId]) . "'>$editButtonText</button>
</div>";
?>
