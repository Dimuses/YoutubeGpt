<?php
declare(strict_types=1);

namespace frontend\models\forms;

use common\components\YoutubeClient;
use common\dto\VideoDto;
use common\models\Channel;
use common\services\ChannelService;
use Yii;
use yii\base\Model;

class VideoAddForm extends Model
{
    public $video_url;

    private $video;

    public function __construct(
        public YoutubeClient  $client,
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

    public function validateVideo($attribute, $params): void
    {
        $videoId = $this->extractVideoId($this->video_url);

        if (!$videoId) {
            $this->addError($attribute, Yii::t('video', 'Unable to extract video ID from URL.'));
            return;
        }
        $channelId = $this->getChannelIdByVideoId($videoId);

        if (!$channelId) {
            $this->addError($attribute, Yii::t('video', 'Incorrect video URL or video does not exist.'));
        }
    }

    private function extractVideoId($url)
    {
        preg_match('/v=([^\&]+)/', $url, $matches);
        return $matches[1] ?? null;
    }

    private function getChannelIdByVideoId($videoId): ?string
    {
        /** @var Channel $channelModel */
        $channelModel = $this->channelService->getUserChannel(\Yii::$app->user->id);
        $params = [
            'channelId' => $channelModel?->channel_id,
            'maxResults' => 50,

        ];

        $videos = $this->client->videoListByChannel('snippet', $params);

        /** @var VideoDto $video */
        foreach ($videos as $video) {
            if ($video->videoId === $videoId) {
                $this->video = $video;
                return $video->channelId;
            }
        }
        return null;
    }

    public function getVideo()
    {
        return $this->video;
    }
}
