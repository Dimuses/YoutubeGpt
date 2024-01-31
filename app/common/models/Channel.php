<?php
declare(strict_types=1);

namespace common\models;

use common\constants\Tables;
use Yii;
use yii\db\ActiveRecord;

/**
 * @property int $user_id
 * @property string $channel_id
 * @property string $name
 *
 * @property User $user
 */
class Channel extends ActiveRecord
{
    /**
     * @return string
     */
    public static function tableName()
    {
        return Tables::CHANNELS;
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['user_id', 'channel_id', 'name'], 'required'],
            [['user_id'], 'integer'],
            [['channel_id', 'name'], 'string', 'max' => 255],
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }
}
