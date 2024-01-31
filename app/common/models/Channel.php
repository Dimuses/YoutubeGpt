<?php
declare(strict_types=1);

namespace common\models;

use common\constants\Tables;
use Yii;
use yii\db\ActiveRecord;

class Channel extends ActiveRecord
{
    public static function tableName()
    {
        return Tables::CHANNELS;
    }

    public function rules()
    {
        return [
            [['user_id', 'channel_id', 'name'], 'required'],
            [['user_id'], 'integer'],
            [['channel_id', 'name'], 'string', 'max' => 255],
        ];
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }
}