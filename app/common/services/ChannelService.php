<?php
declare(strict_types=1);

namespace common\services;

use common\repositories\ChannelRepository;

class ChannelService
{

    public function __construct(
        public ChannelRepository $chanelRepository,
    ){}

    public function getUserChannel($userId): \common\models\Channel|array|\yii\db\ActiveRecord|null
    {
        return $this->chanelRepository->getChannel(['user_id' => $userId]);
    }
}


