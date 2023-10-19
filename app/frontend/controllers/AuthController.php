<?php

namespace frontend\controllers;

use common\components\YoutubeClient;
use frontend\component\AuthHandler;
use Yii;
use yii\authclient\ClientInterface;
use yii\web\Controller;

/**
 * Site controller
 */
class AuthController extends Controller
{
    /*public function actions()
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
    }*/

    public function actionCallback(YoutubeClient $youtube)
    {
        $session = Yii::$app->session;
        if($youtube->getClient()){
            $this->redirect($session->get('referrer', 'index'));
        }
    }
}
