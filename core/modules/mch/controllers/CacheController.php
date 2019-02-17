<?php
/**
 * Created by IntelliJ IDEA.
 * User: luwei
 * Date: 2017/11/9
 * Time: 11:54
 */

namespace app\modules\mch\controllers;


use app\modules\mch\models\CacheCleanForm;

class CacheController extends Controller
{
    public function actionIndex()
    {
        $this->checkIsAdmin();
        if (\Yii::$app->request->isPost) {
            $form = new CacheCleanForm();
            $form->attributes = \Yii::$app->request->post();
            return $form->save();
        } else {
            return $this->render('index');
        }
    }
}