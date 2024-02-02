<?php

use yii\helpers\Html;
use yii\bootstrap5\Tabs;
use yii\web\View;

/** @var yii\web\View $this */
/** @var common\models\Video $model */

$this->title = $model->title;
$this->params['breadcrumbs'][] = ['label' => Yii::t('video', 'Videos'), 'url' => ['index']];
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
                        'label'   => Yii::t('video', 'Video'),
                        'content' => $this->render('video_tab', ['model' => $model]),
                        'options' => ['id' => 'video-tab']
                    ],
                    [
                        'label'   => Yii::t('video', 'Description'),
                        'content' => $this->render('localizations_tab', ['model' => $model, 'videoId' => $videoId]),
                        'options' => ['id' => 'description-tab']
                    ],
                    [
                        'label'   => Yii::t('video', 'Comments'),
                        'options' => ['id' => 'comments-tab'],
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
