<?php
declare(strict_types=1);

namespace frontend\models\forms;

use common\components\YoutubeClient;
use yii\base\Model;

class VideoAddForm extends Model
{
    public $video_url;

    private $video;

    public function __construct(
        public YoutubeClient $client,
        public ChannelService $channelService,
        $config = [])

    {
        parent::__construct($config);
    }

    public function rules()
    {
        return [
            ['video_url', 'required'],
            ['video_url', 'validateVideo'],
        ];
    }

    public function validateVideo($attribute, $params)
    {
        $videoId = $this->extractVideoId($this->video_url);

        if (!$videoId) {
            $this->addError($attribute, 'Невозможно извлечь ID видео из URL.');
            return;
        }

        $channelId = $this->getChannelIdByVideoId($videoId);

        if (!$channelId) {
            $this->addError($attribute, 'Некорректный URL видео или видео не существует.');
        }

    }

    private function extractVideoId($url)
    {
        preg_match('/v=([^\&]+)/', $url, $matches);
        return $matches[1] ?? null;
    }

    private function getChannelIdByVideoId($videoId): ?string
    {
        $youtube = $this->client->getYoutubeService();
        $response = $youtube->videos->listVideos('snippet', array('id' => $videoId));

        if (!empty($response->getItems())) {
            $this->video = $response->getItems()[0];
            $channelId = $this->video->getSnippet()->getChannelId();


        } else {
            return null;
        }
    }
}
