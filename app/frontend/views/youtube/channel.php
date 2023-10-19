<?php
use yii\helpers\Html;

$this->title = 'Список видео';
?>

<h1><?= Html::encode($this->title) ?></h1>

<div class="video-list">
    <?php foreach ($videos as $video): ?>
        <div class="video-item">
            <a href="https://www.youtube.com/watch?v=<?= $video['videoId'] ?>" target="_blank">
                <img src="<?= $video['thumbnailUrl'] ?>" alt="<?= $video['title'] ?>" />
            </a>
            <h3><?= Html::encode($video['title']) ?></h3>
        </div>
    <?php endforeach; ?>
</div>
