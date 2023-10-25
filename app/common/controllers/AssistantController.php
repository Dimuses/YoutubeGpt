<?php

namespace common\controllers;

use common\models\repositories\AssistantRepository;
use common\components\YoutubeClient;
use common\models\Assistant;
use common\models\search\AssistantSearch;
use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * AssistantController implements the CRUD actions for Assistant model.
 */
class AssistantController extends Controller
{
    protected AssistantRepository $assistantRepository;

    /**
     * @inheritDoc
     */
    public function behaviors()
    {
        return array_merge(
            parent::behaviors(),
            [
                'verbs' => [
                    'class' => VerbFilter::className(),
                    'actions' => [
                        'delete' => ['POST'],
                    ],
                ],
            ]
        );
    }


    public function __construct($id, $module, AssistantRepository $assistantRepository, $config = [])
    {
        $this->assistantRepository = $assistantRepository;
        parent::__construct($id, $module, $config);
    }


    public function actionIndex()
    {
        $searchModel = new AssistantSearch();
        $dataProvider = $searchModel->search($this->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    public function actionCreate()
    {
        $model = new Assistant();

        if ($this->request->isPost) {
            if ($model->load($this->request->post()) && $model->save()) {
                return $this->redirect(['view', 'id' => $model->id]);
            }
        } else {
            $model->loadDefaultValues();
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($this->request->isPost && $model->load($this->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
            'assistants' => $this->assistantRepository->getAll()
        ]);
    }

    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }


    protected function findModel($id)
    {
        if (($model = Assistant::findOne(['id' => $id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
    }

    public function actionSettings()
    {
        return $this->render('settings', ['assistants' => $this->assistantRepository->getAllCreatedByAdmin()]);
    }
}
