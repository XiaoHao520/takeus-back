<?php

/**
 * Created by IntelliJ IDEA.
 * User: luwei
 * Date: 2017/10/8
 * Time: 18:22
 */

namespace app\modules\admin\behaviors;

use yii\base\ActionFilter;
use yii\web\Controller;

class AdminBehavior extends ActionFilter
{
    public $only;
    public $ignore;

    public function events()
    {
        return [
            Controller::EVENT_BEFORE_ACTION => 'beforeAction',
        ];
    }

    /**
     * @param \yii\base\InlineAction $e
     */
    public function beforeAction($e)
    {
        if (\Yii::$app->admin->isGuest) {
            if (\Yii::$app->request->isAjax) {
                \Yii::$app->response->data = [
                    'code' => -1,
                    'msg' => '请先登录'
                ];
            } else {
                \Yii::$app->response->redirect(\Yii::$app->urlManager->createUrl([
                    'admin/passport/login',
                    'return_url' => \Yii::$app->request->absoluteUrl,
                ]))->send();
            }
            return false;
        }
        if (\Yii::$app->admin->id == 1) {
            return true;
        }
        if (is_array($this->ignore) && in_array($e->id, $this->ignore))
            return true;
        if (is_array($this->only) && !in_array($e->id, $this->only))
            return true;

        if (\Yii::$app->request->isAjax) {
            \Yii::$app->response->data = [
                'code' => 1,
                'msg' => '您不是超级管理员，无操作权限',
            ];
            return false;
        } else {
            \Yii::$app->response->redirect(\Yii::$app->urlManager->createUrl([
                'admin/default/index',
            ]))->send();
            return false;
        }
    }
}