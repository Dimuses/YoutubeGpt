<?php

use common\constants\Tables;
use yii\db\Migration;

/**
 * Handles the creation of table `comments`.
 */
class m231014_143523_create_comments_table extends Migration
{

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable(Tables::COMMENTS, [
            'id' => $this->primaryKey(),
            'video_id' => $this->string(32)->notNull(),
            'text' => $this->text()->notNull(),
            'replied' => $this->tinyInteger(1)->defaultValue(0),
            'conversation' => $this->tinyInteger(1)->defaultValue(0),
            'comment_id' => $this->string(36)->null(),
            'parent_id' => $this->string(36)->null(),
            'avatar' => $this->string(128),
            'author' => $this->string(255),
            'comment_date' => $this->dateTime(),
            'is_deleted' =>$this->boolean()->defaultValue(false),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ]);

        $this->createIndex(
            'idx-comments-video_id',
            Tables::COMMENTS,
            'video_id'
        );

        $this->addForeignKey(
            'fk-comments-video_id',
            Tables::COMMENTS,
            'video_id',
            Tables::VIDEOS,
            'id',
            'CASCADE'
        );
    }
    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-comments-video_id', Tables::COMMENTS);
        $this->dropIndex('idx-comments-video_id', Tables::COMMENTS);
        $this->dropTable(Tables::COMMENTS);
    }
}
