<?php

namespace frontend\controllers;

use common\components\YoutubeClient;
use common\models\Comments;
use common\models\repositories\AssistantRepository;
use common\models\search\VideoSearch;
use common\models\Video;
use frontend\models\forms\FindReplaceForm;
use Yii;
use yii\data\Pagination;
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
        private AssistantRepository $assistantRepository,
        $config = [])
    {
        parent::__construct($id, $module, $config);
    }

    public function actionCreateAll()
    {
        $channelId = $this->youtubeService->getChannelId();
        $videos = $this->youtubeService->videoListByChannel('snippet', ['channelId' => $channelId, 'maxResults' => 50]);

        foreach ($videos as $videoData) {
            $video = Video::findOne(['video_id' => $videoData['videoId']]);
            if ($video === null) {
                $video = new Video();
            }

            $video->channel_id = $channelId;
            $video->video_id = $videoData['videoId'];

            if ($video->title !== $videoData['title']) {
                $video->title = $videoData['title'];
            }

            $fileName = $video->video_id;
            $extension = pathinfo(parse_url($videoData['thumbnailUrl'], PHP_URL_PATH), PATHINFO_EXTENSION);
            $fullFileName = "$fileName.$extension";

            if ($video->image !== $fullFileName) {
                $imagePath = '@common/files/videos/' . $fullFileName;
                file_put_contents(Yii::getAlias($imagePath), file_get_contents($videoData['thumbnailUrl']));
                $video->image = $fullFileName;
            }

            $videoLanguage = $videoData['defaultLanguage'] ?? null;
            $video->description = $videoData['description'];
            $video->localizations = $videoData['localizations'];
            $video->default_language = $videoLanguage;
            if ($video->save()) {
                Yii::info("Видео {$video->title} сохранено в базу данных", 'app');
            } else {
                Yii::error("Ошибка при сохранении видео {$video->title} в базу данных: " . json_encode($video->errors), 'app');
            }
        }
        return $this->redirect(['index']);
    }


    /**
     * Lists all Video models.
     *
     * @return string
     */
    public function actionIndex()
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
    public function actionView($id)
    {
        $video = $this->findModel($id);

        $query = Comments::find()->where(['video_id' => $video->video_id]);

        $count = $query->count();
        $pagination = new Pagination(['totalCount' => $count, 'defaultPageSize' => 10]);

        $comments = $query->offset($pagination->offset)
            ->limit($pagination->limit)
            ->all();


        return $this->render('view', [
            'model' => $video,
            'comments' => $comments,
            'pagination' => $pagination,
            'assistants' => $this->assistantRepository->getAll()
        ]);
    }


    public function actionFindAndReplace()
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
    public function actionCreate()
    {
        $model = new Video();

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

    /**
     * Updates an existing Video model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param int $id ID
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($this->request->isPost && $model->load($this->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
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
        if (($model = Video::findOne(['id' => $id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
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
            throw new \yii\web\NotFoundHttpException('Изображение не найдено.');
        }
    }
}
