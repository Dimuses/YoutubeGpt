<?php

use yii\bootstrap5\Tabs;
use yii\helpers\Html;
use yii\widgets\DetailView;

/** @var yii\web\View $this */
/** @var common\models\Video $model */

$this->title = $model->title;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Videos'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);

$comments ??= [];
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    .edit-button-container {
        position: relative;
    }

    .edit-icon, .save-icon {
        position: absolute;
        top: 5px;
        right: 5px;
        cursor: pointer;
    }

    .edit-icon {
        display: inline-block;
    }

    .save-icon {
        display: none;
    }

    .edit-mode .edit-icon {
        display: block;
    }

    .edit-mode .save-icon {
        display: inline-block;
    }


    .video-view {
        margin-bottom: 40px;
        border-bottom: 1px solid #e7e7e7;
        padding-bottom: 20px;
    }

    .comments-section {
        margin-top: 20px;
    }

    .comment-item {
        margin-bottom: 20px;
        padding: 10px;
        border: 1px solid #e7e7e7;
        border-radius: 5px;
    }

    .comment-item p {
        margin-bottom: 10px;
    }

    .reply-form {
        display: flex;
        align-items: flex-start;
    }

    .reply-form textarea {
        width: 60%;
        resize: vertical;
        margin-right: 10px;
    }

    .video-container {
        position: relative;
        padding-bottom: 56.25%;
        padding-top: 30px;
        height: 0;
        overflow: hidden;
    }

    .video-container iframe,
    .video-container object,
    .video-container embed {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
    }


</style>


<div class="container">
    <div class="video-view">

        <h1><?= Html::encode($this->title) ?></h1>

        <p>
            <?= Html::a(Yii::t('app', 'Update'), ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
            <?= Html::a(Yii::t('app', 'Delete'), ['delete', 'id' => $model->id], [
                'class' => 'btn btn-danger',
                'data'  => [
                    'confirm' => Yii::t('app', 'Are you sure you want to delete this item?'),
                    'method'  => 'post',
                ],
            ]) ?>
        </p>
        <div class="video-container">
            <iframe width="560" height="315" src="https://www.youtube.com/embed/<?= Html::encode($model->video_id) ?>" frameborder="0" allowfullscreen></iframe>
        </div>
        <br>
        <?php
            $localizations = $model->localizations;
            $tabs = [];

            foreach ($localizations as $lang => $data) {
                $tabs[] = [
                    'label' => $lang,
                    'content' => "<strong>Title:</strong> {$data['title']}<br>
                                  <strong>Description:</strong> " . nl2br($data['description']),
                ];
            }

            echo Tabs::widget(['items' => $tabs]);
        ?>

        <?php /*= DetailView::widget([
            'model'      => $model,
            'attributes' => [
                'channel_id',
                'video_id',
                'title',
                [
                    'attribute' => 'description',
                    'format'    => 'raw',
                    'value'     => function ($model) {
                        return nl2br($model->description) . '
                                <div class="edit-button-container">
                                    <span class="edit-icon glyphicon glyphicon-pencil"></span>
                                    <span class="save-icon glyphicon glyphicon-save"></span>
                                </div>';
                    }
                ],
            ],
        ]) */?>
    </div>

    <div class="comments-section">
        <div class="comments-section">
            <h2>
                Комментарии
                <?= Html::a('Обновить комментарии', ['comment/get-comments', 'videoId' => $model->video_id], [
                    'class' => 'btn btn-primary btn-sm',
                    'data-pjax' => 1
                ]) ?>
            </h2>
            <?php \yii\widgets\Pjax::begin(['id' => 'comments-pjax']); ?>
                <?php foreach ($comments as $comment): ?>
                    <div class="row comment-item">
                        <div class="col-md-6">
                            <p><strong><?= Html::encode($comment->author) ?>:</strong> <?= Html::encode($comment->text) ?></p>
                        </div>
                        <div class="col-md-6">
                            <div class="reply-form">
                                <?= Html::beginForm(['controller-name/reply-comment'], 'post'); ?>
                                <?= Html::textarea('reply', '', ['placeholder' => 'Ответить на комментарий...']); ?>
                                <div class="btn-group" role="group">
                                    <?= Html::submitButton('Сгенерировать ответ', ['class' => 'btn btn-secondary']); ?>
                                    <?= Html::submitButton('Отправить', ['class' => 'btn btn-primary']); ?>
                                </div>
                                <?= Html::endForm(); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php \yii\widgets\Pjax::end(); ?>
        </div>
    </div>
</div>

<?php
$script = <<<JS
    $(document).on('click', '.edit-icon', function() {
        var container = $(this).closest('.edit-button-container');
        var description = container.prev();
        description.attr('contenteditable', 'true').focus();
        container.addClass('edit-mode');
    });
    
    $(document).on('click', '.save-icon', function() {
        var container = $(this).closest('.edit-button-container');
        var description = container.prev();
        description.attr('contenteditable', 'false');
        container.removeClass('edit-mode');
    });
    
    /* $('#update-comments-btn').on('click', function(e) {
        e.preventDefault();
        $.pjax.reload({container: '#comments-pjax'});
    });*/

JS;

$this->registerJs($script);
?>
