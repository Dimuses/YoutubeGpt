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
    $this->createTable(Tables::GENERATED_RESPONSES, [
        'id' => $this->primaryKey(),
        'comment_id' => $this->string()->notNull(),
        'video_id' => $this->string()->notNull(),
        'sent_text' => $this->text()->notNull(),
        'generated_at' => $this->dateTime()->notNull(),
    ]);
}

    public function safeDown()
    {
        $this->dropTable(Tables::GENERATED_RESPONSES);
    }

}
