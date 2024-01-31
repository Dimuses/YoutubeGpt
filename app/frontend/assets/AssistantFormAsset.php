<?php
declare(strict_types=1);

namespace frontend\assets;

use yii\web\AssetBundle;

class AssistantFormAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $js = [
        'js/assistant-form.js',
    ];
    public $css = [
        'css/assistant-form.css',
    ];
    public $depends = [
        'yii\web\YiiAsset',
    ];
}
