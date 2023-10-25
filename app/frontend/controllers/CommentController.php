<?php

namespace frontend\controllers;

use common\components\YoutubeClient;
use common\jobs\GenerateRepliesJob;
use common\models\Comments;
use common\models\repositories\CommentsRepository;
use common\models\search\VideoSearch;
use common\models\Video;
use Yii;
use yii\filters\VerbFilter;
use yii\helpers\Html;
use yii\web\Controller;
use yii\web\MethodNotAllowedHttpException;
use yii\web\NotFoundHttpException;

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
                    'class' => VerbFilter::className(),
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
            $comment->author = $commentData['author'];
            $comment->text = $commentData['text'];
            $comment->replied = 0;
            $comment->conversation = 0;
            $comment->created_at = new \yii\db\Expression('NOW()');
            $comment->updated_at = new \yii\db\Expression('NOW()');
            $comment->avatar = $commentData['avatar'];
            $comment->comment_id = $commentData['comment_id'];
            $comment->comment_date = date('Y-m-d H:i:s', strtotime($commentData['date']));

            if (!$comment->save()) {
                Yii::error('Ошибка при сохранении комментария: ' . json_encode($comment->getErrors()));
            }
        }
        return $this->redirect(\Yii::$app->request->referrer ?? ['video/index']);
    }

    public function actionGenerateReplies()
    {
        $allCommentsIds = $this->commentsRepository->getAllByUser('id');

        Yii::$app->queue->push(new GenerateRepliesJob([
            'comments' => $allComments,
            'assistantId' => $selectedAssistantId,
        ]));
    }

    public function actionReplyComment()
    {
        if ($this->request->isPost) {
            $commentId = $this->request->post('comment_id');
            $reply = $this->request->post('reply');
            $response = $this->youtubeClient->replyToComment($commentId, Html::encode($reply));
            if ($response) {
                return $this->redirect($this->request->referrer);
            }
        }else{
            throw new MethodNotAllowedHttpException();
        }
    }
    
}
