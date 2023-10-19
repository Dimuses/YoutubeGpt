<?php

namespace frontend\controllers;

use common\components\YoutubeClient;
use common\models\Comments;
use common\models\search\VideoSearch;
use common\models\Video;
use Yii;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

/**
 * VideoController implements the CRUD actions for Video model.
 */
class CommentController extends Controller
{
    private YoutubeClient $youtubeService;

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

    public function __construct($id, $module, YoutubeClient $youtubeClient, $config = [])
    {
        $this->youtubeService = $youtubeClient;
        parent::__construct($id, $module, $config);
    }

    public function actionGetComments($videoId)
    {
        $comments = $this->youtubeService->commentsListFromVideo($videoId);
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
            $comment->comment_date = date('Y-m-d H:i:s', strtotime($commentData['date']));

            if (!$comment->save()) {
                Yii::error('Ошибка при сохранении комментария: ' . json_encode($comment->getErrors()));
            }
        }
        return $this->redirect(\Yii::$app->request->referrer);
    }
}
