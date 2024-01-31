<?php

use frontend\assets\VideoAsset;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\widgets\Pjax;

/* @var $searchModel  */
/* @var $foundVideos array */

VideoAsset::register($this);
?>

<h2 class="search-and-replace-heading"><?= Yii::t('video', 'Search and replace')?></h2>

<?php Pjax::begin(['id' => 'find-replace-pjax']); ?>
<?php $form = ActiveForm::begin(); ?>

<?= $form->field($searchModel, 'searchText')
    ->textarea()
    ->label(Yii::t('video', 'Search text')) ?>
<hr>
<div class="form-group">
    <?= Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary form-group-button', 'name' => 'action', 'value' => 'find', 'data-pjax' => 1]) ?>
</div>

<?php if ($foundVideos): ?>
    <p><?= Yii::t('app', 'Found {n, plural, =0{no videos} =1{one video} other{# videos}} with the following text:', ['n' => count($foundVideos)]) ?></p>
    <ul class="video-list">
        <?php foreach ($foundVideos as $video): ?>
            <li class="video-list-item">
                <?= Html::img(['image', 'name' => $video['thumbnail']], ['class' => 'video-thumbnail']) ?>
                <?= Html::encode($video['title']) ?>
            </li>
        <?php endforeach; ?>
    </ul>
    <?= $form->field($searchModel, 'replaceText')
        ->textarea()
        ->label(Yii::t('video', 'Text for replace')) ?>
<?php endif; ?>
<hr>
<?= Html::submitButton(Yii::t('video', 'Replace'), ['class' => 'btn btn-secondary form-group-button', 'name' => 'action', 'value' => 'replace', 'disabled' => empty($foundVideos)]) ?>
<?php ActiveForm::end(); ?>

<?php Pjax::end(); ?>
