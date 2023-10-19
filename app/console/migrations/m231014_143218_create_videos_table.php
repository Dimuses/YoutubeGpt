<?php

use common\constants\Tables;
use yii\db\Migration;

/**
 * Handles the creation of table `videos`.
 */
class m231014_143218_create_videos_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable(Tables::VIDEOS, [
            'id' => $this->primaryKey(),
            'channel_id' => $this->integer()->notNull(),
            'video_id' => $this->string()->notNull(),
            'title' => $this->string()->notNull(),
            'description' => $this->text(),
            'image' => $this->string(64),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);


        $this->createIndex(
            'idx-videos-channel_id',
            Tables::VIDEOS,
            'channel_id'
        );
        $this->addForeignKey(
            'fk-videos-channel_id',
            Tables::VIDEOS,
            'channel_id',
            Tables::CHANNELS,
            'id',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-videos-channel_id', Tables::VIDEOS);
        $this->dropIndex('idx-videos-channel_id', Tables::VIDEOS);
        $this->dropTable(Tables::VIDEOS);
    }
}
