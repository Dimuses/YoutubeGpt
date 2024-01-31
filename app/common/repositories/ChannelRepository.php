<?php
declare(strict_types=1);

namespace common\repositories;

use common\models\Channel;
use yii\db\ActiveRecord;

class ChannelRepository
{

    /**
     *
     * @return Channel[]|ActiveRecord[]
     */
    public function getAll()
    {
        return Channel::find()->all();
    }

    /**
     *
     * @param int $id
     * @return Channel|null
     */
    public function getById($id)
    {
        return Channel::findOne($id);
    }

    /**
     *
     * @param Channel $channel
     * @return bool
     */
    public function save(Channel $channel)
    {
        return $channel->save();
    }

    public function delete(Channel $channel)
    {
        return $channel->delete();
    }

    public function getAllByUser($column = null): array
    {
        try {
            if (!\Yii::$app->user->isGuest) {
                $query = Channel::find()->where(['user_id' => 1]);
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