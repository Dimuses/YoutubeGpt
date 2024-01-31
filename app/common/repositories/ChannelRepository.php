<?php
declare(strict_types=1);

namespace common\repositories;

use common\models\Channel;

class ChannelRepository
{
    public function getChannel($where): Channel|array|\yii\db\ActiveRecord|null
    {
        return Channel::find()->where($where)->one();
    }
}