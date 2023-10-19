<?php

use common\models\Video;
use yii\grid\SerialColumn;
use yii\helpers\Html;
use yii\helpers\StringHelper;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\widgets\Pjax;

$this->title = Yii::t('app', 'Videos');
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="video-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a(Yii::t('app', 'Add manualy'), ['create'], ['class' => 'btn btn-success']) ?>
        <?= Html::a(Yii::t('app', 'Add all videos'), ['create-all'], ['class' => 'btn btn-success']) ?>
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
                }
            ],
        ],
    ]); ?>

    <?php Pjax::end(); ?>

</div>

<?php

$this->registerJs(
        /** @lang JavaScript */ "
    $('[data-toggle=\"tooltip\"]').tooltip({
        placement: 'top',  /* Adjust as per requirement: top, right, bottom, left */
        boundary: 'window',  /* Keeps the tooltip within the viewport */
        html: true
    });
", \yii\web\View::POS_READY);
?>


<style>
    /* Tooltip custom styling */
    .tooltip {
        font-size: 14px;  /* Adjust font size as needed */
    }

    .tooltip-inner {
        max-width: 600px; /* Increase width of tooltip */
        padding: 10px 15px;  /* Add more padding for spacing */
        text-align: left;  /* Left align text inside tooltip */
        background-color: #333;  /* Dark background color for contrast */
        color: #ffffff;  /* White text color */
        border-radius: 5px;  /* Rounded corners */
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);  /* Slight shadow for depth */
    }

</style>
