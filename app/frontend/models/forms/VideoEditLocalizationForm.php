<?php
declare(strict_types=1);

namespace frontend\models\forms;

use common\components\YoutubeClient;
use common\dto\VideoDto;
use common\models\Channel;
use common\services\ChannelService;
use Yii;
use yii\base\Model;

class VideoEditLocalizationForm extends Model
{
    public $video_url;

    private $video;

    public function rules()
    {
        return [
            ['video_url', 'required'],
            ['video_url', 'validateVideo'],
        ];
    }

}
