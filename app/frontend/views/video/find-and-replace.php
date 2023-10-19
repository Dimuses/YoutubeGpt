<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\widgets\Pjax;

/* @var $searchModel  */
/* @var $foundVideos array */
?>

<h2>Найти / заменить</h2>

<?php Pjax::begin(['id' => 'find-replace-pjax']); ?>
    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($searchModel, 'searchText')
        ->textarea()
        ->label('Текст для поиска') ?>
    <hr>
    <div class="form-group">
        <?= Html::submitButton('Найти', ['class' => 'btn btn-primary', 'name' => 'action', 'value' => 'find', 'data-pjax' => 1]) ?>

    </div>

    <?php if ($foundVideos): ?>
        <p style="margin: 10px 0">Найдено <?= count($foundVideos) ?> видео со следующим текстом:</p>
        <ul>
            <?php foreach ($foundVideos as $video): ?>
                <li>
                    <?= Html::img(['image', 'name' => $video['thumbnail']], ['style' => 'padding: 3px 0; width: 90px']) ?>
                    <?= Html::encode($video['title']) ?>
                </li>
            <?php endforeach; ?>
        </ul>
        <?= $form->field($searchModel, 'replaceText')
            ->textarea()
            ->label('Текст для замены') ?>
    <?php endif; ?>
    <hr>
    <?= Html::submitButton('Заменить', ['class' => 'btn btn-secondary', 'name' => 'action', 'value' => 'replace', 'disabled' => empty($foundVideos)]) ?>
    <?php ActiveForm::end(); ?>

<?php Pjax::end(); ?>
