<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\QueueJobs $model */

$this->title = Yii::t('app', 'Create Queue Jobs');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Queue Jobs'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="queue-jobs-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
