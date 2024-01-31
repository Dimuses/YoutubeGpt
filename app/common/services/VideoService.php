<?php

namespace common\services;

use common\models\Video;
use common\repositories\VideoRepository;

class VideoService
{
    public function __construct(
      public  VideoRepository $videoRepository
    ){}

    public function findVideoByIdAndUser($videoId, $userId): Video|array|\yii\db\ActiveRecord|null
    {
        return $this->videoRepository->getByIdAndUser($videoId, $userId);
    }


}