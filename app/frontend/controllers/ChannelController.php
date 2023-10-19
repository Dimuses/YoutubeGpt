<?php

// controllers/ChannelController.php
namespace frontend\controllers;

use Yii;
use yii\web\Controller;
use common\models\Channel;
use yii\data\ActiveDataProvider;

class ChannelController extends Controller
{
    public function actionIndex()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => Channel::find(),
        ]);

        return $this->render('index', ['dataProvider' => $dataProvider]);
    }

    public function actionCreate()
    {
        $model = new Channel();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Канал успешно добавлен.');
            return $this->redirect(['index']);
        }

        return $this->render('create', ['model' => $model]);
    }

    public function actionView($id)
    {
        $model = Channel::findOne($id);
        if (!$model) {
            throw new \yii\web\NotFoundHttpException('Канал не найден.');
        }

        return $this->render('view', ['model' => $model]);
    }

    public function actionDelete($id)
    {
        $model = Channel::findOne($id);
        if (!$model) {
            throw new \yii\web\NotFoundHttpException('Канал не найден.');
        }

        $model->delete();

        Yii::$app->session->setFlash('success', 'Канал успешно удален.');
        return $this->redirect(['index']);
    }
}
