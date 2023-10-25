<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\Assistant $model */

$this->title = Yii::t('app', 'Create Assistant');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Assistants'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="assistant-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
        'assistants' => $assistants,
    ]) ?>

</div>
