<?php

use common\models\Assistant;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\widgets\Pjax;
/** @var yii\web\View $this */
/** @var common\models\search\AssistantSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = Yii::t('assistant', 'Assistants');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="assistant-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a(Yii::t('assistant', 'Create Assistant'), ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?php Pjax::begin(); ?>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            'name',
            [
                'attribute' => 'settings',
                'format' => 'raw',
                'value' => fn($model) => implode(', ', $model->settings),
            ],
            [
                'attribute' => 'parent_id',
                'format' => 'raw',
                'value' => fn($model) => $model?->parent?->name,
            ],
            [
                'class' => ActionColumn::className(),
                'urlCreator' => function ($action, Assistant $model, $key, $index, $column) {
                    return Url::toRoute([$action, 'id' => $model->id]);
                 }
            ],
        ],
    ]); ?>

    <?php Pjax::end(); ?>

</div>
