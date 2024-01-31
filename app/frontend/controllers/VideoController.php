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
          $this->processVideo($videoDto);
        }
        return $this->redirect(['index']);
    }

    private function processVideo(VideoDto $videoDto): void
    {
        $video = Video::findOne(['video_id' => $videoDto->videoId]);
        if ($video === null) {
            $video = new Video();
        }

        $video->channel_id = $videoDto->channelId;
        $video->video_id = $videoDto->videoId;

        if ($video->title !== $videoDto->title) {
            $video->title = $videoDto->title;
        }

        $fileName = $video->video_id;
        $extension = pathinfo(parse_url($videoDto->thumbnailUrl, PHP_URL_PATH), PATHINFO_EXTENSION);
        $fullFileName = "$fileName.$extension";

        if ($video->image !== $fullFileName) {
            $imagePath = '@common/files/videos/' . $fullFileName;
            file_put_contents(Yii::getAlias($imagePath), file_get_contents($videoDto->thumbnailUrl));
            $video->image = $fullFileName;
        }

        $videoLanguage = $videoDto->defaultLanguage ?? null;
        $video->description = $videoDto->description;
        $video->localizations = $videoDto->localizations;
        $video->default_language = $videoLanguage;

        if ($video->save()) {
            Yii::info(Yii::t('video', 'Video {title} saved in the database', ['title' => $video->title]), 'app');
        } else {
            Yii::error(Yii::t('video', 'Error saving video {title} to the database: {errors}', [
                'title' => $video->title,
                'errors' => json_encode($video->errors),
            ]), 'app');
        }
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
            'assistants' => $this->assistantService->getAllAssistants()
        ]);
    }


    public function actionFindAndReplace(): string
    {
        $searchModel = new FindReplaceForm();
        $foundVideos = [];

        if ($searchModel->load(Yii::$app->request->post()) && $searchModel->validate()) {
            $action = Yii::$app->request->post('action');

            $videosFromLocalizations = Video::find()->where(['like', 'JSON_EXTRACT(localizations, "$.*.description")', $searchModel->searchText])->all();

            if ($action == 'find') {
                foreach ($videosFromLocalizations as $video) {
                    $foundVideos[] = [
                        'title' => $video->title,
                        'thumbnail' => $video->image,
                    ];
                }
            } elseif ($action == 'replace' && !empty($videosFromLocalizations)) {
                foreach ($videosFromLocalizations as $video) {
                    $localizations = $video->localizations;
                    foreach ($localizations as $lang => $data) {
                        $localizations[$lang]['description'] = str_replace($searchModel->searchText, $searchModel->replaceText, $data['description']);
                    }
                    $video->localizations = $localizations;
                    if($this->youtubeService->updateVideoLocalizations($video->video_id, $localizations, $video->default_language))
                    {
                        //TODO Надобы сделать автоподтверждение по апи
                        $video->save();
                    }
                }
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
                $this->processVideo($video);
                return $this->redirect(['index']);
            }
        }
        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing Video model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param int $id ID
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id): \yii\web\Response|string
    {
        //TODO недоступно в данном релизе
       /* $model = $this->findModel($id);

        if ($this->request->isPost && $model->load($this->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);*/
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

}
