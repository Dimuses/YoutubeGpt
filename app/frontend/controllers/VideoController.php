<?php
declare(strict_types=1);

namespace frontend\controllers;

use common\components\YoutubeClient;
use common\dto\VideoDto;
use common\models\search\VideoSearch;
use common\models\Video;
use common\services\AssistantService;
use common\services\VideoService;
use frontend\models\forms\FindReplaceForm;
use frontend\models\forms\VideoAddForm;
use frontend\models\search\CommentSearch;
use Yii;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

/**
 * VideoController implements the CRUD actions for Video model.
 */
class VideoController extends Controller
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
            ]
        );
    }

    public function __construct(
        $id,
        $module,
        private YoutubeClient $youtubeService,
        private AssistantService $assistantService,
        private VideoService $videoService,
        $config = [])
    {
        parent::__construct($id, $module, $config);
    }

    public function actionCreateAll(): \yii\web\Response
    {
        $channelId = $this->youtubeService->getChannelId();
        $videos = $this->youtubeService->videoListByChannel('snippet', ['channelId' => $channelId, 'maxResults' => 50]);

        foreach ($videos as $videoDto) {
          $this->videoService->processVideo($videoDto);
        }
        return $this->redirect(['index']);
    }

    /**
     * Lists all Video models.
     *
     * @return string
     */
    public function actionIndex(): string
    {
        $searchModel = new VideoSearch();
        $dataProvider = $searchModel->search($this->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Video model.
     * @param int $id ID
     * @return string
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id): string
    {
        $video = $this->findModel($id);
        $commentSearch = new CommentSearch($video, Yii::$app->request->get('filter'));
        list($pagination, $comments) = $commentSearch->search();

        return $this->render('view', [
            'model' => $video,
            'comments' => $comments,
            'pagination' => $pagination,
            'videoId' => $id,
            'assistants' => $this->assistantService->getAllAssistants()
        ]);
    }

    public function actionUpdateLocalization($videoId)
    {
        $video = Video::findOne(['id' => $videoId]);

        if ($video === null) {
            throw new NotFoundHttpException(Yii::t('video', 'Video not found'));
        }

        if ($video->load(Yii::$app->request->post(), '') && $video->validate()) {
            $this->updateLocalizations($video, $this);
        }

        return true;
    }


    public function actionFindAndReplace(): string
    {
        $searchModel = new FindReplaceForm();
        $foundVideos = [];

        if ($searchModel->load(Yii::$app->request->post()) && $searchModel->validate()) {
            $action = Yii::$app->request->post('action');

            $videosFromLocalizations = Video::find()->where(['like', 'JSON_EXTRACT(localizations, "$.*.description")', $searchModel->searchText])->all();
            $videosFromLocalizations = Video::find()->where(['like', 'JSON_EXTRACT(localizations, "$.*.description")', $searchModel->searchText])->all();

            if ($action == 'find') {
                $foundVideos = $this->processFind($videosFromLocalizations, $foundVideos);
            } elseif ($action == 'replace' && !empty($videosFromLocalizations)) {
                $this->processReplace($videosFromLocalizations, $searchModel);
            }
        }

        return $this->render('find-and-replace', [
            'searchModel' => $searchModel,
            'foundVideos' => $foundVideos,
        ]);
    }



    /**
     * Creates a new Video model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return string|\yii\web\Response
     */
    public function actionCreate(): \yii\web\Response|string
    {
        /** @var VideoAddForm $model */
        $model = Yii::createObject(VideoAddForm::class);

        if ($this->request->isPost) {
            if ($model->load(Yii::$app->request->post()) && $model->validate()) {
                $video = $model->getVideo();
                $this->videoService->processVideo($video);
                return $this->redirect(['index']);
            }
        }
        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing Video model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param int $id ID
     * @return \yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();
        return $this->redirect(['index']);
    }

    /**
     * Finds the Video model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id ID
     * @return Video the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        $model = $this->videoService->findVideoByIdAndUser($id, Yii::$app->user->id);
        return  $model ?: throw new NotFoundHttpException(Yii::t('video', 'The requested page does not exist.'));

    }

    public function actionImage($name)
    {
        $path = Yii::getAlias('@common/files/videos/') . $name;

        if (file_exists($path)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $path);
            finfo_close($finfo);

            Yii::$app->response->headers->add('Content-Type', $mimeType);
            return Yii::$app->response->sendFile($path);
        } else {
            throw new \yii\web\NotFoundHttpException(Yii::t('video', 'Image not found'));
        }
    }

    /**
     * @param array $videosFromLocalizations
     * @param FindReplaceForm $searchModel
     * @return void
     * @throws \Exception
     */
    public function processReplace(array $videosFromLocalizations, FindReplaceForm $searchModel): void
    {
        foreach ($videosFromLocalizations as $video) {
            $localizations = $video->localizations;
            foreach ($localizations as $lang => $data) {
                $localizations[$lang]['description'] = str_replace($searchModel->searchText, $searchModel->replaceText, $data['description']);
            }
            $video->localizations = $localizations;
            if ($this->youtubeService->updateVideoLocalizations($video->video_id, $localizations, $video->default_language)) {
                //TODO Надобы сделать автоподтверждение по апи
                $video->save();
            }
        }
    }

    /**
     * @param array $videosFromLocalizations
     * @param array $foundVideos
     * @return array
     */
    public function processFind(array $videosFromLocalizations, array $foundVideos): array
    {
        foreach ($videosFromLocalizations as $video) {
            $foundVideos[] = [
                'title'     => $video->title,
                'thumbnail' => $video->image,
            ];
        }
        return $foundVideos;
    }

}
