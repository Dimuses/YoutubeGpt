<?php

use yii\helpers\Html;

?>
<div class="container mt-5">
    <div class="row">
        <div class="col-md-6">
            <h1 class="mb-4">Welcome to <strong>YoutubeGpt</strong></h1>
            <p class="lead">Transforming the way you interact with your YouTube audience!</p>
            <p>YoutubeGpt is an innovative AI-powered assistant designed to help you manage your YouTube channel with
                ease and efficiency. Reply to comments, update video details, and much more - all automated to save you
                time and enhance your audience engagement.</p>
            <ul class="mt-4 mb-4">
                <li>Automated, personalized responses to comments</li>
                <li>Easy search and replace feature for video text updates</li>
                <li>Streamlined process for content management</li>
                <li>User-friendly interface for hassle-free navigation</li>
            </ul>
            <?= Html::a(Yii::t('app', 'Get Started'), '/site/login', ['class' => "btn btn-primary"]) ?>
        </div>
        <div class="col-md-6">
            <?= Html::img(Yii::getAlias('@web/images/YoutubeGpt.png'), ['alt' => 'Youtube Gpt', 'height' => '50%']) ?>
        </div>
    </div>
</div>
