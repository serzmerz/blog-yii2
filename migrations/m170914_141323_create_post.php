<?php

use yii\db\Migration;

class m170914_141323_create_post extends Migration
{
    public function safeUp()
    {
        $this->createTable('post', [
           'id' => $this->primaryKey(),
           'title' => $this->string(),
           'description' => $this->text(),
            'body' => $this->text(),
            'user_id' => $this->integer()
        ]);

        $this->insert('post', [
            'title' => 'Post#1',
            'description' => 'Some description for post#1',
            'body' => 'Body for post#1',
            'user_id' => 1
        ]);

        $this->insert('post', [
            'title' => 'Post#2',
            'description' => 'Some description for post#2',
            'body' => 'Body for post#2',
            'user_id' => 1
        ]);
    }

    public function safeDown()
    {
        $this->delete('post', ['id' => 1]);
        $this->delete('post', ['id' => 2]);
        $this->dropTable('post');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m170914_141323_create_post cannot be reverted.\n";

        return false;
    }
    */
}
