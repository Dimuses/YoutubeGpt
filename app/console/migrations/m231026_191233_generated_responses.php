<?php

use common\constants\Tables;
use yii\db\Migration;

/**
 * Class m231026_191233_generated_responses
 */
class m231026_191233_generated_responses extends Migration
{

    public function safeUp()
    {
        if (!$this->db->getTableSchema(Tables::COMMENTS)) {
            throw new \RuntimeException('Таблица "comments" не существует.');
        }
        if (!$this->db->getTableSchema(Tables::COMMENTS)->getColumn('comment_id')) {
            throw new \RuntimeException('Столбец "comment_id" в таблице "comments" не существует.');
        }
        if (!$this->indexExists(Tables::COMMENTS, 'idx-comments-comment_id')) {
            $this->createIndex(
                'idx-comments-comment_id',
                Tables::COMMENTS,
                'comment_id'
            );
        }
        if (!$this->indexExists(Tables::VIDEOS, 'idx-videos-video_id')) {
            $this->createIndex(
                'idx-videos-video_id',
                Tables::VIDEOS,
                'video_id'
            );
        }
        if (!$this->db->getTableSchema(Tables::GENERATED_ANSWERS)) {
            $this->createTable(Tables::GENERATED_ANSWERS, [
                'id'           => $this->primaryKey(),
                'comment_id'   => $this->string()->notNull(),
                'video_id'     => $this->string()->notNull(),
                'text'         => $this->text()->notNull(),
                'tokens'       => $this->string(32),
                'generated_at' => $this->dateTime()->notNull(),
            ]);

            $this->addForeignKey(
                'fk-generated_answers-comment_id',
                Tables::GENERATED_ANSWERS,
                'comment_id',
                Tables::COMMENTS,
                'comment_id',
                'CASCADE'
            );

            $this->addForeignKey(
                'fk-generated_answers-video_id',
                Tables::GENERATED_ANSWERS,
                'video_id',
                Tables::VIDEOS,
                'video_id',
                'CASCADE'
            );
        }
    }

    public function safeDown()
    {
        $this->dropTable(Tables::GENERATED_ANSWERS);
    }

    public function indexExists($tableName, $indexName) {
        $sql = "SHOW INDEX FROM {$tableName} WHERE Key_name=:indexName";
        return $this->db->createCommand($sql, [':indexName' => $indexName])->queryOne() !== false;
    }

}
