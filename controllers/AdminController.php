<?php

namespace app\controllers;


use Yii;
use yii\web\Response;
use yii\widgets\ActiveForm;

class AdminController extends \amnah\yii2\user\controllers\AdminController
{
    /**
     * Create a new User model. If creation is successful, the browser will
     * be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        /** @var \app\models\User $user */
        /** @var \amnah\yii2\user\models\Profile $profile */

        $user = $this->module->model("User");
        $user->setScenario("admin");
        $profile = $this->module->model("Profile");

        $post = Yii::$app->request->post();
        $userLoaded = $user->load($post);
        $profile->load($post);

        // validate for ajax request
        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ActiveForm::validate($user, $profile);
        }

        if ($userLoaded && $user->validate() && $profile->validate()) {
            $user->role_id = 1;
            $user->save(false);
            $profile->setUser($user->id)->save(false);

            $auth = Yii::$app->authManager;
            $authorRole = $auth->getRole($post["User"]["role_id"]);
            $auth->assign($authorRole, $user->getId());

            /** @var \amnah\yii2\user\models\UserToken $userToken */
            $userToken = $this->module->model("UserToken");
            $userToken = $userToken::generate($user->id, $userToken::TYPE_EMAIL_ACTIVATE);

            $user->sendEmailConfirmationForAdminCreateUser($userToken);
            return $this->redirect(['view', 'id' => $user->id]);
        }

        // render
        $auth = Yii::$app->authManager;

        return $this->render('create', compact('user', 'profile'));
    }

    /**
     * Update an existing User model. If update is successful, the browser
     * will be redirected to the 'view' page.
     * @param string $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        // set up user and profile
        $user = $this->findModel($id);
        $user->setScenario("admin");
        $profile = $user->profile;

        $post = Yii::$app->request->post();

        $userLoaded = $user->load($post);
        $profile->load($post);

        // validate for ajax request
        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ActiveForm::validate($user, $profile);
        }

        // load post data and validate
        if ($userLoaded && $user->validate() && $profile->validate()) {

            $newPassword = $post["User"]["newPassword"];

            if(!empty($newPassword)){
                Yii::$app->mailer->compose()
                    ->setTo($post["User"]["email"])
                    ->setSubject('Ваш пароль изменен администратором')
                    ->setHtmlBody('<b>Новый пароль: '.$newPassword.'</b>')
                    ->send();
            }

            $user->role_id = 1;
            $user->save(false);
            $profile->setUser($user->id)->save(false);

            $auth = Yii::$app->authManager;
            $auth->revokeAll($user->getId());
            $authorRole = $auth->getRole($post["User"]["role_id"]);
            $auth->assign($authorRole, $user->getId());

            return $this->redirect(['view', 'id' => $user->id]);
        }
        // render
        return $this->render('update', compact('user', 'profile'));
    }

    /**
     * Delete an existing User model. If deletion is successful, the browser
     * will be redirected to the 'index' page.
     * @param string $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        // delete profile and userTokens first to handle foreign key constraint
        $user = $this->findModel($id);
        $profile = $user->profile;

        $auth = Yii::$app->authManager;
        $auth->revokeAll($user->getId());

        $userToken = $this->module->model("UserToken");
        $userAuth = $this->module->model("UserAuth");
        $userToken::deleteAll(['user_id' => $user->id]);
        $userAuth::deleteAll(['user_id' => $user->id]);
        $profile->delete();
        $user->delete();

        return $this->redirect(['index']);
    }
}