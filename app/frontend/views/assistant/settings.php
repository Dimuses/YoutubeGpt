<?php

use yii\helpers\Html;

/* @var $assistents array */
?>

<h3><?= Yii::t('assistant', 'Assistant settings') ?></h3>
<ul>
    <?php foreach ($assistants as $assistant): ?>
        <li>
            <?= Html::encode($assistant->name) ?>
        </li>
    <?php endforeach; ?>
</ul>

<?= Html::beginForm(['assistant/create'], 'post') ?>
    <?= Html::submitButton(Yii::t('assistant', 'Create new assistant'), ['class' => 'btn btn-success']) ?>
<?= Html::endForm() ?>
