<?php

namespace common\repositories;

use common\models\Comments;
use Google\Service\YouTube\Comment;
use yii\base\Exception;
use yii\db\ActiveRecord;

class CommentsRepository
{

    /**
     *
     * @return Comments[]|ActiveRecord[]
     */
    public function getAll()
    {
        return Comments::find()->all();
    }

    /**
     *
     * @param int $id
     * @return Comments|null
     */
    public function getById($id)
    {
        return Comments::findOne($id);
    }

    /**
     *
     * @param Comments $comment
     * @return bool
     */
    public function save(Comments $comment)
    {
        return $comment->save();
    }

    public function delete(Comments $comment)
    {
        return $comment->delete();
    }

    public function getAllByUser($column = null): array
    {
        try {
            if (!\Yii::$app->user->isGuest) {
                $query = Comments::find()->where(['user_id' => 1]);
                if ($column) {
                    return $query->select($column)->column();
                } else {
                    return $query
                        ->all();
                }
            }
        } catch (\Exception $e) {
        }
        return [];
    }

    public function createComment($data)
    {
        $replyComment = new Comments();
        foreach ($replyComment->attributes as $index => $attribute) {
            $replyComment->$attribute = $data[$attribute] ?: throw new Exception();
        }

      /*  $replyComment->video_id = $data['videoId'] ?? throw new Exception;
        $replyComment->author = $response->getSnippet()->getAuthorDisplayName();
        $replyComment->text = $response->getSnippet()->getTextOriginal();
        $replyComment->replied = 0;
        $replyComment->conversation = 0;
        $replyComment->created_at = new \yii\db\Expression('NOW()');
        $replyComment->updated_at = new \yii\db\Expression('NOW()');
        $replyComment->avatar = $response->getSnippet()->getAuthorProfileImageUrl();
        $replyComment->comment_id = $response->getId();
        $replyComment->comment_date = date('Y-m-d H:i:s', strtotime($response->getSnippet()->getPublishedAt()));
        $replyComment->parent_id = $commentId;*/

        return $replyComment->save();
    }
}