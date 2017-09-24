<?php

namespace app\components;


class User extends \amnah\yii2\user\components\User
{
    public function getNotificationId(){
        $user = $this->getIdentity();
        return $user ? $user->notification_id : "";
    }
}