<?php
declare(strict_types=1);

namespace frontend\assets;

use yii\web\AssetBundle;

class TooltipAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';

    public $css = [
        'css/tooltip.css',
    ];
    public $js = [
        'js/tooltip.js'
    ];
    public $depends = [
        'yii\web\YiiAsset',
    ];
}
