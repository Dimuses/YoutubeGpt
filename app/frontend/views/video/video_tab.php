<?php

use yii\helpers\Html;

?>
<div class="video-container">
    <iframe width="560" height="315" src="https://www.youtube.com/embed/<?= Html::encode($model->video_id) ?>" frameborder="0" allowfullscreen></iframe>
</div>
<br>
