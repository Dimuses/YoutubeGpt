<?php
declare(strict_types=1);

namespace common\models;

use common\constants\Tables;
use Yii;

/**
 * This is the model class for table "{{%videos}}".
 *
 * @property int $id
 * @property int $channel_id
 * @property string $video_id
 * @property string $title
 * @property string $image
 * @property string|null $description
 * @property string|null $created_at
 * @property string|null $updated_at
 *
 * @property Channel $channel
 * @property Comment[] $comments
 * @property mixed|null $localizations
 * @property mixed|null $default_language
 */
class Video extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName(): string
    {
        return Tables::VIDEOS;
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['channel_id', 'video_id', 'title'], 'required'],
            [['channel_id', 'localizations'], 'safe'],
            [['description'], 'string'],
            [['image'], 'string', 'max' => 64],
            [['created_at', 'updated_at'], 'safe'],
            [['video_id', 'title'], 'string', 'max' => 255],
//            [['channel_id'], 'exist', 'skipOnError' => true, 'targetClass' => Channel::class, 'targetAttribute' => ['channel_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('video', 'ID'),
            'channel_id' => Yii::t('video', 'Channel ID'),
            'video_id' => Yii::t('video', 'Video ID'),
            'title' => Yii::t('video', 'Title'),
            'description' => Yii::t('video', 'Description'),
            'image' => Yii::t('video', 'Image'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * Gets query for [[Channel]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getChannel()
    {
        return $this->hasOne(Channel::class, ['channel_id' => 'channel_id']);
    }

    /**
     * Gets query for [[Comments]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getComments()
    {
        return $this->hasMany(Comment::class, ['video_id' => 'id']);
    }
}
