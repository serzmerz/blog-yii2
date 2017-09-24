<?php

namespace app\controllers;


use amnah\yii2\user\controllers\DefaultController;
use app\models\User;
use Yii;
use yii\web\Response;
use yii\widgets\ActiveForm;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;

class UserController extends DefaultController
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'actions' => ['index', 'confirm','confirm-password', 'resend', 'logout', 'get-admins'],
                        'allow' => true,
                        'roles' => ['?', '@'],
                    ],
                    [
                        'actions' => ['account', 'profile', 'resend-change', 'cancel'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                    [
                        'actions' => ['login', 'register', 'forgot', 'reset', 'login-email', 'login-callback'],
                        'allow' => true,
                        'roles' => ['?'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }
    /**
     * Display registration page
     */
    public function actionRegister()
    {
        /** @var \amnah\yii2\user\models\User $user */
        /** @var \amnah\yii2\user\models\Profile $profile */
        /** @var \amnah\yii2\user\models\Role $role */

        // set up new user/profile objects
        $user = $this->module->model("User", ["scenario" => "register"]);
        $profile = $this->module->model("Profile");

        // load post data
        $post = Yii::$app->request->post();
        if ($user->load($post)) {

            // ensure profile data gets loaded
            $profile->load($post);

            // validate for ajax request
            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return ActiveForm::validate($user, $profile);
            }

            // validate for normal request
            if ($user->validate() && $profile->validate()) {

                // perform registration
                $role = $this->module->model("Role");
                $user->setRegisterAttributes($role::ROLE_USER)->save();
                $profile->setUser($user->id)->save();

                //auth manager
                $auth = Yii::$app->authManager;
                $userRole = $auth->getRole('user');
                $auth->assign($userRole, $user->getId());

                $this->afterRegister($user);

                // set flash
                // don't use $this->refresh() because user may automatically be logged in and get 403 forbidden
                $successText = Yii::t("user", "Successfully registered [ {displayName} ]", ["displayName" => $user->getDisplayName()]);
                $guestText = "";
                if (Yii::$app->user->isGuest) {
                    $guestText = Yii::t("user", " - Please check your email to confirm your account");
                }
                Yii::$app->session->setFlash("Register-success", $successText . $guestText);
            }
        }

        return $this->render("register", compact("user", "profile"));
    }

    /**
     * Account
     */
    public function actionAccount()
    {
        /** @var \amnah\yii2\user\models\User $user */
        /** @var \amnah\yii2\user\models\UserToken $userToken */

        // set up user and load post data
        $user = Yii::$app->user->identity;
        $user->setScenario("account");
        $loadedPost = $user->load(Yii::$app->request->post());

        // validate for ajax request
        if ($loadedPost && Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ActiveForm::validate($user);
        }

        // validate for normal request
        $userToken = $this->module->model("UserToken");
        if ($loadedPost && $user->validate()) {
            $user->notification_id = intval(Yii::$app->request->post("User")["notification_id"]);
            // check if user changed his email
            $newEmail = $user->checkEmailChange();
            if ($newEmail) {
                $userToken = $userToken::generate($user->id, $userToken::TYPE_EMAIL_CHANGE, $newEmail);
                if (!$numSent = $user->sendEmailConfirmation($userToken)) {

                    // handle email error
                    //Yii::$app->session->setFlash("Email-error", "Failed to send email");
                }
            }

            // save, set flash, and refresh page
            $user->save(false);
            Yii::$app->session->setFlash("Account-success", Yii::t("user", "Account updated"));
            return $this->refresh();
        } else {
            $userToken = $userToken::findByUser($user->id, $userToken::TYPE_EMAIL_CHANGE);
        }

        return $this->render("account", compact("user", "userToken"));
    }

    /**
     * Confirm email
     */
    public function actionConfirm($token)
    {
        /** @var \amnah\yii2\user\models\UserToken $userToken */
        /** @var \amnah\yii2\user\models\User $user */

        // search for userToken
        $success = false;
        $email = "";
        $userToken = $this->module->model("UserToken");
        $userToken = $userToken::findByToken($token, [$userToken::TYPE_EMAIL_ACTIVATE, $userToken::TYPE_EMAIL_CHANGE]);
        if ($userToken) {

            // find user and ensure that another user doesn't have that email
            //   for example, user registered another account before confirming change of email
            $user = $this->module->model("User");
            $user = $user::findOne($userToken->user_id);
            $newEmail = $userToken->data;
            if ($user->confirm($newEmail)) {
                $success = true;
                $this->performLogin($user);
                $this->sendMailToAdmins($user->getId());
            }

            // set email and delete token
            $email = $newEmail ?: $user->email;
            $userToken->delete();
        }

        return $this->render("confirm", compact("userToken", "success", "email"));
    }

    public function actionConfirmPassword($token){

        /** @var \amnah\yii2\user\models\User $user */
        /** @var \amnah\yii2\user\models\UserToken $userToken */

        // get user token and check expiration
        $userToken = $this->module->model("UserToken");
        $userToken = $userToken::findByToken($token, [$userToken::TYPE_EMAIL_ACTIVATE, $userToken::TYPE_EMAIL_CHANGE]);
        if (!$userToken) {
            return $this->render('confirm-password', ["invalidToken" => true]);
        }

        // get user and set "reset" scenario
        $success = false;
        $user = $this->module->model("User");
        $user = $user::findOne($userToken->user_id);
        $user->setScenario("reset");

        // load post data and reset user password
        if ($user->load(Yii::$app->request->post()) && $user->save()) {

            /*new lines*/
            $newEmail = $userToken->data;
            if ($user->confirm($newEmail)) {
                $this->performLogin($user);
            }

            // delete userToken and set success = true
            $userToken->delete();
            $success = true;

        }

        return $this->render('confirm-password', compact("user", "success"));
    }

    protected function sendMailToAdmins($id){
        $auth = Yii::$app->authManager;
        $allAdminsId = $auth->getUserIdsByRole('admin');

        $allAdmins = User::findAll($allAdminsId);

        $messages = [];
        foreach ($allAdmins as $user) {
            $messages[] = Yii::$app->mailer->compose()
                ->setSubject('Зарегистрировался новый пользователь')
                ->setHtmlBody('<b>Посмотреть профиль: <a href='.Yii::$app->urlManager->createAbsoluteUrl(['user/admin/view', 'id'=> $id]).'>перейти</a></b>')
                ->setTo($user->email);
        }
        Yii::$app->mailer->sendMultiple($messages);
        }
}