<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var common\models\QueueJobs $model */
/** @var yii\widgets\ActiveForm $form */
?>

<div class="queue-jobs-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'job_id')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'user_id')->textInput() ?>

    <?= $form->field($model, 'type')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'status')->dropDownList([ 'waiting' => 'Waiting', 'processing' => 'Processing', 'completed' => 'Completed', 'failed' => 'Failed', ], ['prompt' => '']) ?>

    <?= $form->field($model, 'created_at')->textInput() ?>

    <?= $form->field($model, 'started_at')->textInput() ?>

    <?= $form->field($model, 'completed_at')->textInput() ?>

    <?= $form->field($model, 'error_message')->textarea(['rows' => 6]) ?>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Save'), ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
