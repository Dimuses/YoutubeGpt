<?php

namespace common\models;

use common\constants\Tables;
use Yii;

/**
 * This is the model class for table "generated_answers".
 *
 * @property int $id
 * @property string $comment_id
 * @property string $video_id
 * @property string $text
 * @property string|null $tokens
 * @property string $generated_at
 *
 * @property Comments $comment
 * @property Video $video
 */
class GeneratedAnswers extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return Tables::GENERATED_ANSWERS;
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['comment_id', 'video_id', 'text', 'generated_at'], 'required'],
            [['text'], 'string'],
            [['generated_at'], 'safe'],
            [['comment_id', 'video_id'], 'string', 'max' => 255],
            [['tokens'], 'string', 'max' => 32],
            [['comment_id'], 'exist', 'skipOnError' => true, 'targetClass' => Comments::class, 'targetAttribute' => ['comment_id' => 'comment_id']],
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
            'comment_id' => Yii::t('app', 'Comment ID'),
            'video_id' => Yii::t('app', 'Video ID'),
            'text' => Yii::t('app', 'Text'),
            'tokens' => Yii::t('app', 'Tokens'),
            'generated_at' => Yii::t('app', 'Generated At'),
        ];
    }

    /**
     * Gets query for [[Comment]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getComment()
    {
        return $this->hasOne(Comments::class, ['comment_id' => 'comment_id']);
    }

    /**
     * Gets query for [[Video]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getVideo()
    {
        return $this->hasOne(Video::class, ['video_id' => 'video_id']);
    }
}
