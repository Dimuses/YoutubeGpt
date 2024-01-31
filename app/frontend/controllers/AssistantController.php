<?php
declare(strict_types=1);

namespace frontend\controllers;

use common\components\YoutubeClient;
use common\models\Assistant;
use common\models\Comments;
use common\models\search\AssistantSearch;
use common\models\search\VideoSearch;
use common\models\Video;
use frontend\models\forms\FindReplaceForm;
use Yii;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

/**
 * VideoController implements the CRUD actions for Video model.
 */
class AssistantController extends \common\controllers\AssistantController
{
    public function actionCreate()
    {
        $model = new Assistant();

        if ($this->request->isPost) {
            if ($model->load($this->request->post())) {
                $model->created_by_admin = 0;
                return $this->redirect(['view', 'id' => $model->id]);
            }
        } else {
            $model->loadDefaultValues();
        }
        return $this->render('create', [
            'model' => $model,
            'assistants' => $this->assistantRepository->getAll()
        ]);
    }

    public function actionIndex()
    {
        $searchModel = new AssistantSearch(['created_by_admin' => 0]);
        $dataProvider = $searchModel->search($this->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }


}
