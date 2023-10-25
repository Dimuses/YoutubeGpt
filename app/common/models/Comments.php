<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%comments}}".
 *
 * @property int $id
 * @property int $video_id
 * @property string $text
 * @property int|null $replied
 * @property int|null $conversation
 * @property string|null $created_at
 * @property string|null $updated_at
 * @property string|null $avatar
 * @property string|null $comment_date
 * @property string|null $author
 *
 * @property Video $video
 * @property mixed|null $comment_id
 */
class Comments extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%comments}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['video_id', 'text'], 'required'],
            [['replied', 'conversation'], 'integer'],
            [['text'], 'string'],
            [['created_at', 'updated_at', 'comment_date', 'comment_id'], 'safe'],
            [['avatar'], 'string', 'max' => 128],
            [['video_id'], 'exist', 'skipOnError' => true, 'targetClass' => Video::class, 'targetAttribute' => ['video_id' => 'video_id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'video_id' => Yii::t('app', 'Video ID'),
            'text' => Yii::t('app', 'Text'),
            'replied' => Yii::t('app', 'Replied'),
            'conversation' => Yii::t('app', 'Conversation'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
            'avatar' => Yii::t('app', 'Avatar'),
            'comment_date' => Yii::t('app', 'Comment Date'),
        ];
    }

    /**
     * Gets query for [[Video]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getVideo()
    {
        return $this->hasOne(Video::class, ['id' => 'video_id']);
    }
}
