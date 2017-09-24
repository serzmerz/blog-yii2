<?php

namespace app\controllers;


use app\models\Post;
use Yii;
use yii\data\Pagination;
use yii\filters\AccessControl;
use yii\helpers\Url;
use yii\web\Controller;

class PostController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['view'],
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['view'],
                        'roles' => ['viewPost'],
                    ]
            ]
            ]
        ];
    }

    public function actionIndex($col = 3)
    {
        $query = Post::find();

        $countQuery = clone $query;
        $pages = new Pagination(['totalCount' => $countQuery->count(), 'defaultPageSize' => $col]);
        $models = $query->offset($pages->offset)
            ->limit($pages->limit)
            ->all();

        if(Yii::$app->request->isAjax){
            return $this->renderAjax('index', [
                'model' => $models,
                'pages' => $pages,
            ]);
        }
        else{
        return $this->render('index', [
            'model' => $models,
            'pages' => $pages,
        ]);
        }
    }

    public function actionView($id)
    {
        $model = Post::findOne($id);
        return $model ? $this->render('view', ['model' => $model])
            : $this->redirect(Url::home());
    }

}