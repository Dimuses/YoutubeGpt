<?php

namespace frontend\controllers;

use frontend\component\AuthHandler;
use Yii;
use yii\authclient\ClientInterface;
use yii\web\Controller;

/**
 * Site controller
 */
class AuthController extends Controller
{
    public function actions()
    {
        return [
            'success-google' => [
                'class'           => 'yii\authclient\AuthAction',
                'successCallback' => [$this, 'onAuthSuccess'],
                'defaultClientId' => 'google'
            ],
        ];
    }

    public function onAuthSuccess($client)
    {
        (new AuthHandler($client))->handle();
    }
}
