<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%assistant}}".
 *
 * @property int $id
 * @property string $name
 * @property string|null $settings
 * @property int|null $parent_id
 * @property int|null $created_by_admin
 * @property string|null $created_at
 * @property string|null $updated_at
 * @property string|null $description
 *
 * @property Assistant[] $assistants
 * @property Assistant $parent
 */
class Assistant extends \yii\db\ActiveRecord
{
    const BY_ADMIN = 1;
    const BY_USER = 2;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%assistant}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['settings', 'created_at', 'updated_at', 'description'], 'safe'],
            [['parent_id', 'created_by_admin'], 'integer'],
            ['created_by_admin', 'default',  'value' => self::BY_ADMIN],
            [['name'], 'string', 'max' => 255],
            [['parent_id'], 'exist', 'skipOnError' => true, 'targetClass' => Assistant::class, 'targetAttribute' => ['parent_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'name' => Yii::t('app', 'Name'),
            'settings' => Yii::t('app', 'Settings'),
            'parent_id' => Yii::t('app', 'Parent ID'),
            'created_by_admin' => Yii::t('app', 'Created By Admin'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * Gets query for [[Assistants]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAssistants()
    {
        return $this->hasMany(Assistant::class, ['parent_id' => 'id']);
    }

    /**
     * Gets query for [[Parent]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getParent()
    {
        return $this->hasOne(Assistant::class, ['id' => 'parent_id']);
    }
}
