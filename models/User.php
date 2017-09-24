<?php

namespace app\models;

use amnah\yii2\user\models\UserToken;
use Yii;
use yii\helpers\Url;
use yii\swiftmailer\Mailer;
use yii\swiftmailer\Message;

class User extends \amnah\yii2\user\models\User
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = [
            // general email and username rules
            [['email', 'username'], 'string', 'max' => 255],
            [['email', 'username'], 'unique'],
            [['email', 'username'], 'filter', 'filter' => 'trim'],
            [['email'], 'email'],
            [['username'], 'match', 'pattern' => '/^\w+$/u', 'except' => 'social', 'message' => Yii::t('user', '{attribute} can contain only letters, numbers, and "_"')],

            // password rules
            [['newPassword'], 'string', 'min' => 3],
            [['newPassword'], 'filter', 'filter' => 'trim'],
            [['newPassword'], 'required', 'on' => ['register', 'reset']],
            [['newPasswordConfirm'], 'required', 'on' => ['reset']],
            [['newPasswordConfirm'], 'compare', 'compareAttribute' => 'newPassword', 'message' => Yii::t('user', 'Passwords do not match')],

            // account page
            [['currentPassword'], 'validateCurrentPassword', 'on' => ['account']],

            // admin crud rules
            [['role_id', 'status'], 'required', 'on' => ['admin']],
            [['status'], 'integer', 'on' => ['admin']],
            [['banned_at'], 'integer', 'on' => ['admin']],
            [['banned_reason'], 'string', 'max' => 255, 'on' => 'admin'],
        ];

        // add required for currentPassword on account page
        // only if $this->password is set (might be null from a social login)
        if ($this->password) {
            $rules[] = [['currentPassword'], 'required', 'on' => ['account']];
        }

        // add required rules for email/username depending on module properties
        if ($this->module->requireEmail) {
            $rules[] = ["email", "required"];
        }
        if ($this->module->requireUsername) {
            $rules[] = ["username", "required"];
        }

        return $rules;
    }

    /**
     * Send email confirmation to user
     * @param UserToken $userToken
     * @return int
     */
    public function sendEmailConfirmationForAdminCreateUser($userToken)
    {
        /** @var Mailer $mailer */
        /** @var Message $message */

        // modify view path to module views
        $mailer = Yii::$app->mailer;
        $oldViewPath = $mailer->viewPath;
        $mailer->viewPath = $this->module->emailViewPath;

        // send email
        $user = $this;
        $profile = $user->profile;
        $email = $userToken->data ?: $user->email;
        $subject = Yii::$app->id . " - " . Yii::t("user", "Email Confirmation");

        $url = Url::toRoute(["/user/confirm-password", "token" => $userToken->token], true);
        $result = $mailer->compose()
            ->setTo($email)
            ->setSubject($subject)
            ->setHtmlBody('<b>Please confirm your email address by clicking the link below:</b>'.$url)
            ->send();

        // restore view path and return result
        $mailer->viewPath = $oldViewPath;
        return $result;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAssignment()
    {
        return $this->hasOne(AuthAssignment::className(), ['user_id' => 'id']);
    }
}
