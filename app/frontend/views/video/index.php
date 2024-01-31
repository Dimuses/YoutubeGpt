<?php

use common\models\Video;
use frontend\assets\TooltipAsset;
use frontend\assets\VideoAsset;
use yii\grid\SerialColumn;
use yii\helpers\Html;
use yii\helpers\StringHelper;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\widgets\Pjax;

$this->title = Yii::t('video', 'Videos');
$this->params['breadcrumbs'][] = $this->title;

TooltipAsset::register($this);
?>

<div class="video-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a(Yii::t('video', 'Add manualy'), ['create'], ['class' => 'btn btn-success']) ?>
        <?= Html::a(Yii::t('video', 'Add all videos'), ['create-all'], ['class' => 'btn btn-success']) ?>
    </p>

    <?php Pjax::begin(); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => SerialColumn::class],
            'title',
            [
                'attribute' => 'description',
                'format' => 'raw',
                'value' => function ($model) {
                    return Html::tag('span', StringHelper::truncate($model->description, 150), [
                        'class' => 'description-field',
                        'data-full-text' => nl2br($model->description),
                        'data-toggle' => 'tooltip',
                        'title' => nl2br($model->description)
                    ]);
                },
            ],
            [
                'attribute' => 'image',
                'format' => 'raw',
                'value' => function($model){
                    return Html::img(['image', 'name' => $model->image]);
                }
            ],
            [
                'class' => ActionColumn::className(),
                'urlCreator' => function ($action, Video $model, $key, $index, $column) {
                    return Url::toRoute([$action, 'id' => $model->id]);
                },
                'template' => '{view}{delete}'
            ],
        ],
    ]); ?>

    <?php Pjax::end(); ?>

</div>


