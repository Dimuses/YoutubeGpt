<?php

use common\constants\Tables;
use yii\db\Migration;

/**
 * Class m231023_120501_insert_default_admin_user
 */
class m231023_120501_insert_default_admin_user extends Migration
{public function safeUp()
{
    $security = Yii::$app->security;
    $password = "demo";
    $this->insert(Tables::USER, [
        'username' => 'admin',
        'auth_key' => $security->generateRandomString(),
        'password_hash' => $security->generatePasswordHash($password),
        'email' => 'admin@example.com',
        'status' => 10,
        'created_at' => time(),
        'updated_at' => time(),
    ]);
}

    public function safeDown()
    {
        $this->delete(Tables::USER, ['username' => 'admin']);
    }
}
