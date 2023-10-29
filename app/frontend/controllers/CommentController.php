<?php

namespace frontend\controllers;

use common\components\YoutubeClient;
use common\jobs\GenerateRepliesJob;
use common\models\Comments;
use common\models\repositories\AssistantRepository;
use common\models\repositories\CommentsRepository;
use dimuses\chatgpt\dto\AnswerSetting;
use dimuses\chatgpt\dto\AnswerSettingsList;
use dimuses\chatgpt\dto\Comment;
use dimuses\chatgpt\providers\ChatGptProvider;
use Yii;
use yii\filters\VerbFilter;
use yii\helpers\Html;
use yii\web\Controller;
use yii\web\MethodNotAllowedHttpException;

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
                                private ChatGptProvider $gptProvider,
        $config = [])
    {
        parent::__construct($id, $module, $config);
    }

    public function actionGetComments($videoId)
    {
        $comments = $this->youtubeClient->commentsListFromVideo($videoId);
        Comments::deleteAll(['video_id' => $videoId]);

        foreach ($comments as $commentData) {
            $comment = new Comments();
            $comment->video_id = $videoId;
            $comment->author = $commentData->author;
            $comment->text = $commentData->text;
            $comment->replied = (int)$commentData->hasReplyFromAuthor;
            $comment->conversation = 0;
            $comment->created_at = new \yii\db\Expression('NOW()');
            $comment->updated_at = new \yii\db\Expression('NOW()');
            $comment->avatar = $commentData->avatar;
            $comment->comment_id = $commentData->comment_id;
            $comment->comment_date = date('Y-m-d H:i:s', strtotime($commentData->date));
            $comment->parent_id = null;


            if (!$comment->save()) {
                Yii::error('Ошибка при сохранении комментария: ' . json_encode($comment->getErrors()));
            } elseif (isset($commentData->replies) && is_array($commentData->replies)) {
                foreach ($commentData->replies as $replyData) {
                    $reply = new Comments();
                    $reply->video_id = $videoId;
                    $reply->author = $replyData->author;
                    $reply->text = $replyData->text;
                    $reply->replied = 0;
                    $reply->conversation = 0;
                    $reply->created_at = new \yii\db\Expression('NOW()');
                    $reply->updated_at = new \yii\db\Expression('NOW()');
                    $reply->avatar = $replyData->avatar;
                    $reply->comment_id = $replyData->reply_id;
                    $reply->comment_date = date('Y-m-d H:i:s', strtotime($replyData->date));
                    $reply->parent_id = $comment->comment_id;

                    if (!$reply->save()) {
                        Yii::error('Ошибка при сохранении ответа на комментарий: ' . json_encode($reply->getErrors()));
                    }
                }
            }
        }
        return $this->redirect(\Yii::$app->request->referrer ?? ['video/index']);
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
        $message = new Comment($this->request->post('comment'));
        $this->gptProvider->setSettingList($settingsList);
        $answer = $this->gptProvider->generateAnswer($message);

        return $answer->text;

    }

    public function actionReplyComment()
    {
        if ($this->request->isPost) {
            $commentId = $this->request->post('comment_id');
            $reply = $this->request->post('reply');
            $videoId = $this->request->post('video_id');
            if ($commentId && $videoId && $reply) {
                $response = $this->youtubeClient->replyToComment($commentId, Html::encode($reply));

                if ($response) {
                    $replyComment = new Comments();
                    $replyComment->video_id = $videoId; // Вам потребуется извлечь videoId из какого-то источника
                    $replyComment->author = $response->getSnippet()->getAuthorDisplayName();
                    $replyComment->text = $response->getSnippet()->getTextOriginal();
                    $replyComment->replied = 0;
                    $replyComment->conversation = 0;
                    $replyComment->created_at = new \yii\db\Expression('NOW()');
                    $replyComment->updated_at = new \yii\db\Expression('NOW()');
                    $replyComment->avatar = $response->getSnippet()->getAuthorProfileImageUrl();
                    $replyComment->comment_id = $response->getId();
                    $replyComment->comment_date = date('Y-m-d H:i:s', strtotime($response->getSnippet()->getPublishedAt()));
                    $replyComment->parent_id = $commentId;

                    if (!$replyComment->save()) {
                        Yii::error('Ошибка при сохранении ответа на комментарий: ' . json_encode($replyComment->getErrors()));
                    }else{
                        $replyComment->parent->replied = 1;
                        $replyComment->save();
                    }
                }
            } else {
                throw new MethodNotAllowedHttpException();
            }
        }
        return $this->redirect($this->request->referrer);
    }
}
