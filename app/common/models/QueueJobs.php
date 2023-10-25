<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%queue_jobs}}".
 *
 * @property int $id
 * @property string $job_id
 * @property int $user_id
 * @property string $type
 * @property string|null $status
 * @property int $created_at
 * @property int|null $started_at
 * @property int|null $completed_at
 * @property string|null $error_message
 *
 * @property User $user
 */
class QueueJobs extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%queue_jobs}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['job_id', 'user_id', 'type', 'created_at'], 'required'],
            [['user_id', 'created_at', 'started_at', 'completed_at'], 'integer'],
            [['status', 'error_message'], 'string'],
            [['job_id', 'type'], 'string', 'max' => 255],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'job_id' => Yii::t('app', 'Job ID'),
            'user_id' => Yii::t('app', 'User ID'),
            'type' => Yii::t('app', 'Type'),
            'status' => Yii::t('app', 'Status'),
            'created_at' => Yii::t('app', 'Created At'),
            'started_at' => Yii::t('app', 'Started At'),
            'completed_at' => Yii::t('app', 'Completed At'),
            'error_message' => Yii::t('app', 'Error Message'),
        ];
    }

    /**
     * Gets query for [[User]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }
}
