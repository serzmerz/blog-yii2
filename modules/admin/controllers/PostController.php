<?php

namespace app\modules\admin\controllers;

use app\models\User;
use Yii;
use app\models\Post;
use app\models\PostSearch;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * PostController implements the CRUD actions for Post model.
 */
class PostController extends Controller
{
    const NOTIFICATION_TYPE_EMAIL = 1;
    const NOTIFICATION_TYPE_BROWSER = 2;
    const NOTIFICATION_TYPE_ALL = 3;

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['managePost'],
                    ]
                ]
            ],
        ];
    }

    /**
     * Lists all Post models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new PostSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

            return $this->render('index', [
                'searchModel' => $searchModel,
                'dataProvider' => $dataProvider,
            ]);

    }

    /**
     * Displays a single Post model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new Post model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Post();

        $loadedPost = $model->load(Yii::$app->request->post());
        $model->user_id = Yii::$app->user->getId();

        if ($loadedPost && $model->save()) {

            $this->sendToBrowser($model->id);
            $this->sendNewsMail($model->id);

            return $this->redirect(['view', 'id' => $model->id]);
        } else {
            return $this->render('create', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Updates an existing Post model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        } else {
            return $this->render('update', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Deletes an existing Post model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the Post model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Post the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Post::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    /**
     * Notify all users to email about new news
     * @param int $id
     */
    protected function sendNewsMail(int $id)
    {
        $users = User::find()->where(['status' => 1])->all();

        $messages = [];
        foreach ($users as $user) {

            $notyId = $user->notification_id;
            if ($notyId === self::NOTIFICATION_TYPE_EMAIL ||
                $notyId === self::NOTIFICATION_TYPE_ALL) {

                $messages[] = Yii::$app->mailer->compose()
                    ->setSubject('Новая новость на нашем сайте')
                    ->setHtmlBody('<b>Посмотреть новость: <a href=' .
                        Yii::$app->urlManager->createAbsoluteUrl(['post/view', 'id' => $id]).
                        '>перейти</a></b>')
                    ->setTo($user->email);

            }

        }
        Yii::$app->mailer->sendMultiple($messages);

    }

    /**
     * Notify user to browser about new news
     * @param $id
     */
    private function sendToBrowser(int $id)
    {
        $notyId = Yii::$app->user->getNotificationId();

        if ($notyId === self::NOTIFICATION_TYPE_BROWSER || $notyId === self::NOTIFICATION_TYPE_ALL) {

            Yii::$app->session->setFlash('success',
                'New news | <a href=' .
                Yii::$app->urlManager->createAbsoluteUrl(['post/view', 'id' => $id])
                . '>Read</a>');

        }
    }
}
