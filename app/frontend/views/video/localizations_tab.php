<?php

use yii\bootstrap5\Tabs;
use yii\helpers\Html;

?>
<br>
<?php
$localizations = $model->localizations;
$tabs = [];

foreach ($localizations as $lang => $data) {
    $tabs[] = [
        'label' => $lang,
        'content' => "<strong>Title:</strong> {$data['title']}<br>
                          <strong>Description:</strong> " . nl2br($data['description']),
    ];
}

echo Tabs::widget(['items' => $tabs]);
?>
