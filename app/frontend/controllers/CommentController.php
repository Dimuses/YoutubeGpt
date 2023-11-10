<?php

namespace frontend\controllers;

use common\components\YoutubeClient;
use common\jobs\GenerateRepliesJob;
use common\models\Comments;
use common\models\GeneratedAnswers;
use common\models\Video;
use common\repositories\AssistantRepository;
use common\repositories\CommentsRepository;
use common\repositories\GeneratedAnswersRepository;
use dimuses\chatgpt\dto\AnswerSetting;
use dimuses\chatgpt\dto\AnswerSettingsList;
use dimuses\chatgpt\dto\Comment;
use dimuses\chatgpt\providers\ChatGptProvider;
use Yii;
use yii\filters\VerbFilter;
use yii\helpers\Html;
use yii\web\Controller;
use yii\web\MethodNotAllowedHttpException;
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
                        'delete' => ['POST'],
                    ],
                ],
            ]
        );
    }


    public function __construct($id,
        $module,
        private YoutubeClient $youtubeClient,
        private CommentsRepository $commentsRepository,
        private AssistantRepository $assistantRepository,
        private GeneratedAnswersRepository $generatedAnswersRepository,
        private ChatGptProvider $gptProvider,
        $config = [])
    {
        parent::__construct($id, $module, $config);
    }

    public function actionGetComments($videoId)
    {
        $youtubeCommentsData = $this->youtubeClient->commentsListFromVideo($videoId);
        $youtubeCommentsIds = [];

        foreach ($youtubeCommentsData as $commentData) {
            $youtubeCommentsIds[] = $commentData->comment_id;
            if (isset($commentData->replies) && is_array($commentData->replies)) {
                foreach ($commentData->replies as $replyData) {
                    $youtubeCommentsIds[] = $replyData->comment_id;
                }
            }
        }

        $existingComments = Comments::find()->where(['video_id' => $videoId])->all();
        $existingCommentIds = array_column($existingComments, 'comment_id');

        $commentsToDelete = array_diff($existingCommentIds, $youtubeCommentsIds);
        foreach ($commentsToDelete as $commentId) {
            Comments::updateAll(['is_deleted' => 1], ['comment_id' => $commentId]);
        }

        foreach ($youtubeCommentsData as $commentData) {
            $this->syncComment($videoId, $commentData);

            if (isset($commentData->replies) && is_array($commentData->replies)) {
                foreach ($commentData->replies as $replyData) {
                    $this->syncComment($videoId, $replyData, $commentData->comment_id);
                }
            }
        }
        return $this->redirect(\Yii::$app->request->referrer ?? ['video/index']);
    }

    private function syncComment($videoId, $commentData, $parentId = null)
    {
        $comment = Comments::findOne(['comment_id' => $commentData->comment_id]);
        if (!$comment) {
            $comment = new Comments();
            $comment->created_at = new \yii\db\Expression('NOW()');
        } else {
            $comment->is_deleted = 0;
        }

        $comment->video_id = $videoId;
        $comment->author = $commentData->author;
        $comment->text = $commentData->text;
        $comment->replied = (int)$commentData->hasReplyFromAuthor;
        $comment->conversation = $parentId ? 1 : 0;
        $comment->updated_at = new \yii\db\Expression('NOW()');
        $comment->avatar = $commentData->avatar;
        $comment->comment_id = $commentData->comment_id;
        $comment->comment_date = date('Y-m-d H:i:s', strtotime($commentData->date));
        $comment->parent_id = $parentId;

        if (!$comment->save()) {
            Yii::error('Ошибка при сохранении комментария: ' . json_encode($comment->getErrors()));
        }
    }

    public function actionGenerateReplies()
    {
        $allCommentsIds = $this->commentsRepository->getAllByUser('id');

        Yii::$app->queue->push(new GenerateRepliesJob([
            'comments'    => $allComments,
            'assistantId' => $selectedAssistantId,
        ]));
    }

    public function actionGenerateReply()
    {
        $assistant = $this->assistantRepository->getById(3);
        if ($parent = $assistant->parent) {
            $assistant->settings = array_merge($parent->settings, $assistant->settings);
        }
        $settingsList = new AnswerSettingsList();
        foreach ($assistant->settings as $index => $item) {
            $settingsList->addSetting(new AnswerSetting($item));
        }
        $comment = $this->request->post('comment');

        if(rand(0, 2) != 1){
            $comment = explode(':', $comment);
            $comment = $comment[1];
        }


        $commentId = $this->request->post('comment_id');
        $videoId = $this->request->post('video_id');
        $video = Video::findOne(['video_id' => $videoId]);

        $message = new Comment("Video: {$video->title}.". $comment);
        $this->gptProvider->setSettingList($settingsList);
        $answer = $this->gptProvider->generateAnswer($message);

        if ($answer->text) {
            $reply = new GeneratedAnswers();
            $reply->comment_id = $commentId;
            $reply->video_id = $videoId;
            $reply->text = $answer->text;
            $reply->tokens = "0"; //TODO
            $reply->generated_at = date('Y-m-d H:i:s');

            $reply->save();
        }
        return $answer->text;
    }

    public function actionReplyComment()
    {
        if ($this->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $commentId = $this->request->post('comment_id');
            $reply = $this->request->post('reply');
            $videoId = $this->request->post('video_id');
            if ($commentId && $videoId && $reply) {
                $response = $this->youtubeClient->replyToComment($commentId, Html::encode($reply));

                if ($response) {
                    $replyComment = new Comments();
                    $replyComment->video_id = $videoId;
                    $replyComment->author = $response['author'];
                    $replyComment->text = $response['text'];
                    $replyComment->replied = $response['replied'];
                    $replyComment->conversation = $response['conversation'];
                    $replyComment->created_at = $response['created_at'];
                    $replyComment->updated_at = $response['updated_at'];
                    $replyComment->avatar = $response['avatar'];
                    $replyComment->comment_id = $response['comment_id'];
                    $replyComment->comment_date = $response['comment_date'];
                    $replyComment->parent_id = $response['parent_id'];

                    if (!$replyComment->save()) {
                        Yii::error('Ошибка при сохранении ответа на комментарий: ' . json_encode($replyComment->getErrors()));
                    } else {
                        $replyComment->parent->replied = 1;
                        if ($replyComment->parent->save()){
                            $this->generatedAnswersRepository->deleteAllBy('comment_id', $commentId);
                        }


                    }
                }
            } else {
                throw new MethodNotAllowedHttpException();
            }
        }
        return 'success';
    }
}
