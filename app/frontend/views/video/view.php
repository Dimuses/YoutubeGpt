<?php

use yii\helpers\Html;
use yii\bootstrap5\Tabs;

/** @var yii\web\View $this */
/** @var common\models\Video $model */

$this->title = $model->title;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Videos'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>

    <div class="container">
        <div class="video-view">
            <h1><?= Html::encode($this->title) ?></h1>
            <p>
                <?= Html::a(Yii::t('app', 'Delete'), ['delete', 'id' => $model->id], [
                    'class' => 'btn btn-danger',
                    'data'  => [
                        'confirm' => Yii::t('app', 'Are you sure you want to delete this item?'),
                        'method'  => 'post',
                    ],
                ]) ?>
            </p>
            <?= Tabs::widget([
                'items' => [
                    [
                        'label'   => 'Видео',
                        'content' => $this->render('video_tab', ['model' => $model]),
                    ],
                    [
                        'label'   => 'Описание',
                        'content' => $this->render('localizations_tab', ['model' => $model]),
                    ],
                    [
                        'label'   => 'Комментарии',
                        'content' => $this->render('comments_tab', [
                            'comments'   => $comments,
                            'model'      => $model,
                            'assistants' => $assistants,
                            'pagination' => $pagination]),
                    ],
                ],
            ]) ?>
        </div>
    </div>

<?php
$this->registerCss(<<<CSS
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
CSS
);

?>