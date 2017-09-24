<?php

use yii\db\Migration;

class m170923_191701_init_notification_types extends Migration
{
    public function safeUp()
    {
        $this->createTable('notification_types', [
            'id' => $this->primaryKey(),
            'title' => $this->string(),
        ]);

        $this->insert('notification_types', [
            'title' => 'Only email'
        ]);
        $this->insert('notification_types', [
            'title' => 'Only browser'
        ]);

        $this->insert('notification_types', [
            'title' => 'All notification'
        ]);
    }

    public function safeDown()
    {
        $this->delete('notification_types', ['id' => 1]);
        $this->delete('notification_types', ['id' => 2]);
        $this->delete('notification_types', ['id' => 3]);
        $this->dropTable('notification_types');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m170923_191701_init_notification_types cannot be reverted.\n";

        return false;
    }
    */
}
