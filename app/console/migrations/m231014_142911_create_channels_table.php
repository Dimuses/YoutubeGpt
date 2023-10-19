<?php

use common\constants\Tables;
use yii\db\Migration;

/**
 * Handles the creation of table `channels`.
 */
class m231014_142911_create_channels_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable(Tables::CHANNELS, [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'channel_id' => $this->integer()->notNull(),
            'name' => $this->string()->notNull(),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ]);

        $this->createIndex(
            'idx-channels-user_id',
            Tables::CHANNELS,
            'user_id'
        );

        $this->addForeignKey(
            'fk-channels-user_id',
            Tables::CHANNELS,
            'user_id',
            Tables::USER,
            'id',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-channels-user_id', Tables::CHANNELS);
        $this->dropIndex('idx-channels-user_id', Tables::CHANNELS);
        $this->dropTable(Tables::CHANNELS);
    }
}
