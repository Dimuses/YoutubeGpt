<?php
declare(strict_types=1);

namespace common\repositories;

use common\models\Video;
use yii\db\ActiveRecord;

class VideoRepository
{
    /**
     * @param $videoId
     * @param $userId
     * @return array|Video|\yii\db\ActiveRecord|null
     */
    public function getByIdAndUser($videoId, $userId): \yii\db\ActiveRecord|array|null|Video
    {
        return Video::find()
            ->alias('v')
            ->joinWith('channel c')
            ->where(['v.id' => $videoId, 'c.user_id' => $userId])
            ->one();
    }

    /**
     *
     * @param int $id
     * @return Video|null
     */
    public function getById($id)
    {
        return Video::findOne($id);
    }

    /**
     *
     * @return Video[]|ActiveRecord[]
     */
    public function getAll()
    {
        return Video::find()->all();
    }

    /**
     *
     * @param Video $video
     * @return bool
     */
    public function save(Video $video)
    {
        return $video->save();
    }

    public function delete(Video $video)
    {
        return $video->delete();
    }
}
