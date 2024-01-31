<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\Assistant $model */

$this->title = Yii::t('assistant', 'Update Assistant: {name}', [
    'name' => $model->name,
]);
$this->params['breadcrumbs'][] = ['label' => Yii::t('assistant', 'Assistants'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Update');
?>
<div class="assistant-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
        'assistants' => $assistants,
    ]) ?>

</div>
