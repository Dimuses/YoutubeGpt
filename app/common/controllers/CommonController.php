<?php
declare(strict_types=1);

namespace common\controllers;

use common\models\Assistant;
use common\models\search\AssistantSearch;
use common\repositories\AssistantRepository;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

/**
 * AssistantController implements the CRUD actions for Assistant model.
 */
class CommonController extends Controller
{
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
                'access' => [
                    'class' => AccessControl::className(),
                    ''
                ],
            ]
        );
    }
}
