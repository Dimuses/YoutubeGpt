<?php

use yii\helpers\Html;

/* @var $assistents array */
?>

<h3>Настройки YouTube ассистента</h3>
<ul>
    <?php foreach ($assistants as $assistant): ?>
        <li>
            <?= Html::encode($assistant->name) ?>
        </li>
    <?php endforeach; ?>
</ul>

<?= Html::beginForm(['assistant/create'], 'post') ?>
    <?= Html::submitButton('Создать нового ассистента', ['class' => 'btn btn-success']) ?>
<?= Html::endForm() ?>
