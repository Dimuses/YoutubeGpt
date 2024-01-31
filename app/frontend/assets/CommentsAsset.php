<?php
namespace frontend\assets;

use yii\web\AssetBundle;

class CommentsAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'css/comments.css',
    ];
    public $js = [
        'js/comments.js'
    ];
    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap5\BootstrapAsset',
    ];
}
