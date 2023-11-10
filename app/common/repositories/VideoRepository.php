<?php
namespace common\repositories;

use common\models\Video;
use yii\db\ActiveRecord;

class VideoRepository
{
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
     * @param int $id
     * @return Video|null
     */
    public function getById($id)
    {
        return Video::findOne($id);
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
