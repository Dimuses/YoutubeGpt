<?php

use common\constants\Tables;
use yii\db\Migration;

/**
 * Handles the creation of table `{{%queue_jobs}}`.
 */
class m231025_123439_create_queue_jobs_table extends Migration
{

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable(Tables::QUEUE_JOBS, [
            'id' => $this->primaryKey(),
            'job_id' => $this->string()->notNull(),
            'user_id' => $this->integer()->notNull(),
            'type' => $this->string()->notNull(),
            'status' => "ENUM('waiting', 'processing', 'completed', 'failed') DEFAULT 'waiting'",
            'created_at' => $this->integer()->notNull(),
            'started_at' => $this->integer(),
            'completed_at' => $this->integer(),
            'error_message' => $this->text()
        ]);

        $this->createIndex('idx-queue_jobs-job_id', Tables::QUEUE_JOBS, 'job_id');
        $this->createIndex('idx-queue_jobs-user_id', Tables::QUEUE_JOBS, 'user_id');
        $this->addForeignKey('fk-queue_jobs-user_id', Tables::QUEUE_JOBS, 'user_id', Tables::USER, 'id', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-queue_jobs-user_id', Tables::QUEUE_JOBS);
        $this->dropIndex('idx-queue_jobs-user_id', Tables::QUEUE_JOBS);
    }
}
