<?php

namespace common\models\repositories;

use common\models\Comments;
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
}