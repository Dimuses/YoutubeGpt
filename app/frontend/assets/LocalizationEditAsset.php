<?php
declare(strict_types=1);

namespace frontend\assets;

use yii\bootstrap5\BootstrapAsset;
use yii\web\AssetBundle;

class LocalizationEditAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $js = [
        'js/localization-edit.js',
        'js/video.js',
    ];
    public $depends = [
        'yii\web\YiiAsset',
        BootstrapAsset::class
    ];
}