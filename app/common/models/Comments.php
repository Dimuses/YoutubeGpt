<?php
declare(strict_types=1);

namespace common\models;

use common\constants\Tables;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\console\widgets\Table;
use yii\db\BaseActiveRecord;
use yii\db\Expression;

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
 * @property-read \yii\db\ActiveQuery $generatedReply
 * @property-read mixed $answers
 * @property-read \yii\db\ActiveQuery $replies
 * @property-read mixed $parent
 * @property int|mixed|null $parent_id
 */
class Comments extends \yii\db\ActiveRecord
{
    public function behaviors()
    {
        return [
            [
                'class'      => TimestampBehavior::class,
                'attributes' => [
                    BaseActiveRecord::EVENT_BEFORE_INSERT => ['created_at'],
                    BaseActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at'],
                ],
                'value'      => new Expression('NOW()'),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName(): string
    {
        return Tables::COMMENTS;
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
            [['created_at', 'updated_at', 'comment_date', 'comment_id', 'parent_id'], 'safe'],
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
            'id'           => Yii::t('app', 'ID'),
            'video_id'     => Yii::t('app', 'Video ID'),
            'text'         => Yii::t('app', 'Text'),
            'replied'      => Yii::t('app', 'Replied'),
            'conversation' => Yii::t('app', 'Conversation'),
            'created_at'   => Yii::t('app', 'Created At'),
            'updated_at'   => Yii::t('app', 'Updated At'),
            'avatar'       => Yii::t('app', 'Avatar'),
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

    public function getParent()
    {
        return $this->hasOne(self::class, ['comment_id' => 'parent_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getReplies()
    {
        return $this->hasMany(Comments::class, ['parent_id' => 'comment_id']);
    }

    public function getAnswers()
    {
        return $this->hasMany(GeneratedAnswers::class, ['comment_id' => 'comment_id'])
            ->orderBy(['generated_at' => SORT_DESC]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGeneratedReply()
    {
        return $this->hasOne(GeneratedAnswers::class, ['comment_id' => 'comment_id'])
            ->orderBy(['generated_at' => SORT_DESC]);
    }
}
