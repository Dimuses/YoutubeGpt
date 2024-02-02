<?php

namespace common\services;

use common\components\YoutubeClient;
use common\dto\VideoDto;
use common\models\Video;
use common\repositories\VideoRepository;
use frontend\controllers\VideoController;
use Yii;

class VideoService
{
    public function __construct(
      public  YoutubeClient $client,
      public  VideoRepository $videoRepository
    ){}

    public function findVideoByIdAndUser($videoId, $userId): Video|array|\yii\db\ActiveRecord|null
    {
        return $this->videoRepository->getByIdAndUser($videoId, $userId);
    }

    public function processVideo(VideoDto $videoDto): bool
    {
        $video = $this->videoRepository->getById($videoDto->videoId);
        if ($video === null) {
            $video = new Video();
        }

        $video->channel_id = $videoDto->channelId;
        $video->video_id = $videoDto->videoId;

        if ($video->title !== $videoDto->title) {
            $video->title = $videoDto->title;
        }

        $fileName = $video->video_id;
        $extension = pathinfo(parse_url($videoDto->thumbnailUrl, PHP_URL_PATH), PATHINFO_EXTENSION);
        $fullFileName = "$fileName.$extension";

        if ($video->image !== $fullFileName) {
            $imagePath = '@common/files/videos/' . $fullFileName;
            file_put_contents(Yii::getAlias($imagePath), file_get_contents($videoDto->thumbnailUrl));
            $video->image = $fullFileName;
        }

        $videoLanguage = $videoDto->defaultLanguage ?? null;
        $video->description = $videoDto->description;
        $video->localizations = $videoDto->localizations;
        $video->default_language = $videoLanguage;

        if ($video->save()) {
            Yii::info(Yii::t('video', 'Video {title} saved in the database', ['title' => $video->title]), 'app');
            return true;
        } else {
            Yii::error(Yii::t('video', 'Error saving video {title} to the database: {errors}', [
                'title'  => $video->title,
                'errors' => json_encode($video->errors),
            ]), 'app');
            return false;
        }
    }

    /**
     * @param Video $video
     * @param VideoController $videoController
     * @return void
     * @throws \Exception
     */
    public function updateLocalizations(Video $video): void
    {
        $localizations = $video->localizations;
        if ($this->client->updateVideoLocalizations($video->video_id, $localizations, $video->default_language)) {
            if ($video->save()) {
                Yii::info(Yii::t('video', 'Video localization updated successfully'), 'video');
            } else {
                Yii::error(Yii::t('video', 'Error saving video localization: {errors}', ['errors' => json_encode($video->errors)]), 'video');
            }
        } else {
            Yii::error(Yii::t('video', 'Error updating video localization on YouTube'), 'video');
        }
    }


}