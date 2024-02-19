<?php

use frontend\assets\AssistantFormAsset;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var common\models\Assistant $model */
/** @var yii\widgets\ActiveForm $form */

$settingsValues = $model->settings ? $model->settings : [''];
AssistantFormAsset::register($this);

?>

<div class="assistant-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>

    <div class="input-fields-wrap">
        <?php foreach ($settingsValues as $index => $value): ?>
            <div class="row mb-2"> <!-- добавлен класс mb-2 для отступа между строками -->
                <div class="col-md-10">
                    <?= $form->field($model, 'settings[]')->textInput(['value' => $value])->label(false) ?>
                </div>
                <div class="col-md-2">
                    <?php if ($index == 0): ?>
                        <button class="add-button btn btn-primary" style="width: 100%;">+</button>
                    <?php else: ?>
                        <button class="remove-button btn btn-danger" style="width: 100%;">-</button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?= $form->field($model, 'parent_id')->dropDownList(ArrayHelper::map($assistants,'id', 'name'), ['prompt' => Yii::t('assistant', 'Select assistant')]) ?>
    <?= $form->field($model, 'description')->textarea() ?>
    <br>
    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Save'), ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
