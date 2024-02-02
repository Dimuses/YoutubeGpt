<?php
declare(strict_types=1);

namespace frontend\controllers;

use common\jobs\GenerateRepliesJob;
use common\repositories\{AssistantRepository, CommentsRepository};
use common\services\CommentsService;
use common\services\VideoService;
use dimuses\chatgpt\dto\{AnswerSetting, AnswerSettingsList, Comment};
use dimuses\chatgpt\providers\ChatGptProvider;
use Yii;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * VideoController implements the CRUD actions for Video model.
 */
class CommentController extends Controller
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
                    'class'   => VerbFilter::className(),
                    'actions' => [
                        'reply-comment' => ['POST'],
                        'generate-reply' => ['POST'],
                    ],
                ],
            ]
        );
    }

    /**
     * @param $id
     * @param $module
     * @param CommentsRepository $commentsRepository
     * @param AssistantRepository $assistantRepository
     * @param ChatGptProvider $gptProvider
     * @param CommentsService $commentsService
     * @param VideoService $videoService
     * @param $config
     */
    public function __construct($id,
        $module,
        public CommentsRepository $commentsRepository,
        public AssistantRepository $assistantRepository,
        public ChatGptProvider $gptProvider,
        public CommentsService $commentsService,
        public VideoService $videoService,
        $config = [])
    {
        parent::__construct($id, $module, $config);
    }

    /**
     * @param $videoId
     * @return Response
     * @throws NotFoundHttpException
     * @throws \Google\Exception
     * @throws \yii\base\InvalidConfigException
     */
    public function actionGetComments($videoId): Response
    {
        $video = $this->videoService->findVideoByIdAndUser($videoId, Yii::$app->user->id);
        if (!$video) {
            throw new NotFoundHttpException(Yii::t('video', 'The video was not found or you do not have access to it'));
        }
        $this->commentsService->refreshComments($videoId, $this);
        return $this->redirect(\Yii::$app->request->referrer ?? ['video/index']);
    }


    /**
     * @return void
     */
    public function actionGenerateReplies($videoId): void
    {
        $allCommentsIds = $this->commentsService->getAllWithoutReply($videoId, Yii::$app->user->id);

        Yii::$app->queue->push(new GenerateRepliesJob([
            'comments'    => $allComments,
            'assistantId' => $selectedAssistantId,
        ]));
    }

    /**
     * @return string
     */
    public function actionGenerateReply()
    {
        $assistant = $this->assistantRepository->getById(3);
        $assistant->settings = array_merge(
            (array)$assistant?->parent?->settings,
            (array)$assistant->settings
        );
        $settingsList = new AnswerSettingsList();
        foreach ($assistant->settings as $index => $item) {
            $settingsList->addSetting(new AnswerSetting($item));
        }
        $comment = $this->request->post('comment');
        $comment = $this->commentsService->filterComment($comment);
        $commentId = $this->request->post('comment_id');
        $videoId = $this->request->post('video_id');
        $video = $this->videoService->findVideoByIdAndUser($videoId, Yii::$app->user->id);

        $message = new Comment("Video: {$video?->title}." . $comment);
        $this->gptProvider->setSettingList($settingsList);
        $answer = $this->gptProvider->generateAnswer($message);
        $this->commentsService->createAnswer($commentId, $videoId, $answer?->text);
        return $answer->text;
    }

    /**
     * @return string
     * @throws \Google\Exception
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\web\MethodNotAllowedHttpException
     */
    public function actionReplyComment(): string
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $commentId = $this->request->post('comment_id');
        $reply = $this->request->post('reply');
        $videoId = $this->request->post('video_id');
        $this->commentsService->createAndSaveReply($commentId, $videoId, $reply);
        return 'success';
    }

}
