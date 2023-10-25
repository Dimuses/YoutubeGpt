<?php

namespace common\models;

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
    public static function tableName()
    {
        return '{{%videos}}';
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
            'id' => Yii::t('app', 'ID'),
            'channel_id' => Yii::t('app', 'Channel ID'),
            'video_id' => Yii::t('app', 'Video ID'),
            'title' => Yii::t('app', 'Title'),
            'description' => Yii::t('app', 'Description'),
            'image' => Yii::t('app', 'Image'),
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
        return $this->hasOne(Channel::class, ['id' => 'channel_id']);
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
