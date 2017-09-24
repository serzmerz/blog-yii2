<?php

use yii\db\Migration;

class m170915_103252_rbac_role_init extends Migration
{
    public function safeUp()
    {
        $auth = Yii::$app->authManager;

        // добавляем разрешение "viewPost"
        $viewPost = $auth->createPermission('viewPost');
        $viewPost->description = 'View a post';
        $auth->add($viewPost);

        // добавляем роль "user" и даём роли разрешение "viewPost"
        $user = $auth->createRole('user');
        $auth->add($user);
        $auth->addChild($user, $viewPost);

        // добавляем разрешение "createPost"
        $managePost = $auth->createPermission('managePost');
        $managePost->description = 'Manage a post';
        $auth->add($managePost);


        // добавляем роль "author" и даём роли разрешение "createPost"
        $author = $auth->createRole('author');
        $auth->add($author);
        $auth->addChild($author, $managePost);
        $auth->addChild($author, $user);

        // добавляем разрешение "manageUser"
        $manageUser = $auth->createPermission('manageUser');
        $manageUser->description = 'manage User';
        $auth->add($manageUser);

        // добавляем роль "admin" и даём роли разрешение "updatePost"
        // а также все разрешения роли "author"
        $admin = $auth->createRole('admin');
        $auth->add($admin);
        $auth->addChild($admin, $manageUser);
        $auth->addChild($admin, $author);

        // Назначение ролей пользователям. 1 и 2 это IDs возвращаемые IdentityInterface::getId()
        $auth->assign($admin, 1);
    }

    public function safeDown()
    {
        echo "m170915_103252_rbac_role_init cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m170915_103252_rbac_role_init cannot be reverted.\n";

        return false;
    }
    */
}
