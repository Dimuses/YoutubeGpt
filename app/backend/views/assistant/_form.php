<?php

use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var common\models\Assistant $model */
/** @var yii\widgets\ActiveForm $form */

$settingsValues = $model->settings ? $model->settings : [''];
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

    <?= $form->field($model, 'parent_id')->dropDownList(ArrayHelper::map($assistants,'id', 'name'), ['prompt' => '-- Выберите ассистента --']) ?>
    <?= $form->field($model, 'description')->textarea() ?>
    <br>
    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Save'), ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>

<?php
$this->registerJs(/** @lang JavaScript */ "
    var maxFields = 10;
    var addButton = $('.add-button');
    var wrapper = $('.input-fields-wrap');
    var x = 1;

    $(addButton).click(function(e) {
        e.preventDefault();
        if (x < maxFields) {
            x++;
            $(wrapper).append('<div class=\"row\"><div class=\"col-md-10\"><input type=\"text\" name=\"Assistant[settings][]\" class=\"form-control\"/></div><div class=\"col-md-2\"><button class=\"remove-button btn btn-danger\" style=\"width: 100%;\">-</button></div></div>');
        }
    });

    $(wrapper).on('click', '.remove-button', function(e) {
        e.preventDefault();
        $(this).closest('.row').remove();
        x--;
    });
");

$this->registerCss(/** @lang CSS */ "
     .remove-button {
        cursor: pointer;
    }
    .input-fields-wrap .row:not(:first-child) {
        margin-top: 10px;
    }
    .input-fields-wrap {
        margin-top: 15px;
    }
");
?>





