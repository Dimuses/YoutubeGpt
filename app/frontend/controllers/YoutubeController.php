<?php
declare(strict_types=1);

namespace frontend\controllers;

use common\components\YoutubeClient;
use Yii;
use yii\web\Controller;
use Google_Client;
use Google_Service_YouTube;

class YoutubeController extends Controller
{
    private YoutubeClient $youtubeService;

    public function __construct($id, $module, YoutubeClient $youtubeClient, $config = [])
    {
        $this->youtubeService = $youtubeClient;
        parent::__construct($id, $module, $config);
    }


    public function actionCallback()
    {
        $session = Yii::$app->session;
        if($this->youtubeService->getClient()){
            return $this->redirect($session->get('referrer', 'index'));
        }
    }
}
