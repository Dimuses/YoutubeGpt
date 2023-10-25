<?php

use common\constants\Tables;
use yii\db\Migration;

/**
 * Handles the creation of table `{{%assistent}}`.
 */
class m231023_080632_create_assistent_table extends Migration
{
    public function safeUp()
    {
        $this->createTable(Tables::ASSISTANT, [
            'id'               => $this->primaryKey(),
            'name'             => $this->string(255)->notNull(),
            'description'      => $this->string(255),
            'settings'         => $this->json(),
            'parent_id'        => $this->integer(),
            'created_by_admin' => $this->tinyInteger(),
            'created_at'       => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at'       => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ]);

        $this->addForeignKey(
            'fk-assistent-parent_id',
            Tables::ASSISTANT, 'parent_id',
            Tables::ASSISTANT, 'id',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-assistent-parent_id', Tables::ASSISTANT);
        $this->dropTable(Tables::ASSISTANT);
    }
}
