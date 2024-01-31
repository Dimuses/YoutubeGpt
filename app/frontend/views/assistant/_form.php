<?php

use frontend\assets\AssistantFormAsset;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var common\models\Assistant $model */
/** @var yii\widgets\ActiveForm $form */

AssistantFormAsset::register($this);

?>

<div class="assistant-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>

    <div class="input-fields-wrap">
        <div class="row">
            <div class="col-md-10">
                <?= $form->field($model, 'settings[]')->textInput()->label(false) ?>
            </div>
            <div class="col-md-2">
                <button class="add-button btn btn-primary" style="width: 100%;">+</button>
            </div>
        </div>
    </div>

    <?= $form->field($model, 'parent_id')->dropDownList(ArrayHelper::map($assistants,'id', 'name'), ['prompt' => Yii::t('assistant', 'Select assistant')]) ?>
    <?= $form->field($model, 'description')->textarea() ?>
    <br>
    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Save'), ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
