<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/6/27
 * Time: 10:56
 */

namespace app\modules\mch\controllers;

use app\models\Attr;
use app\models\AttrGroup;
use app\models\Card;
use app\models\Cat;
use app\models\Goods;
use app\models\GoodsCat;
use app\models\Photographer;
use app\models\PhotographerLevel;
use app\models\PostageRules;
use app\models\ProductList;
use app\models\User;
use app\modules\mch\models\CopyForm;
use app\modules\mch\models\GoodsForm;
use app\modules\mch\models\GoodsQrcodeForm;
use app\modules\mch\models\SetGoodsSortForm;
use yii\data\Pagination;
use yii\web\HttpException;

/**
 * Class GoodController
 * @package app\modules\mch\controllers
 * 商品
 */
class PhotographerController extends Controller
{

    /**
     * 商品分类删除
     * @param int $id
     */
    public function actionGoodClassDel($id = 0)
    {
        $dishes = Cat::findOne(['id' => $id, 'is_delete' => 0, 'store_id' => $this->store->id]);
        if (!$dishes) {
            return [
                'code' => 1,
                'msg' => '商品分类删除失败或已删除',
            ];
        }
        $dishes->is_delete = 1;
        if ($dishes->save()) {
            return [
                'code' => 0,
                'msg' => '成功',
            ];
        } else {
            foreach ($dishes->errors as $errors) {
                return [
                    'code' => 1,
                    'msg' => $errors[0],
                ];
            }
        }
    }

    public function actionGetCatList($parent_id = 0)
    {
        $list = Cat::find()->select('id,name')->where(['is_delete' => 0, 'parent_id' => $parent_id, 'store_id' => $this->store->id])->asArray()->all();
        return [
            'code' => 0,
            'data' => $list,
        ];
    }


    /**
     * 商品管理
     * @return string
     */
    public function actionPhotographers($keyword = null, $status = null)
    {

        $query = Photographer::find()->alias('p')->where(['p.store_id' => $this->store->id, 'p.is_delete' => 0]);
        if ($status != null) {
            $query->andWhere('p.status=:status', [':status' => $status]);
        }
        $query->leftJoin(['pl' => PhotographerLevel::tableName()], 'pl.id=p.level_id');
        $query->leftJoin(['u' => User::tableName()], 'u.id=p.user_id');


        $query->select('p.*,u.nickname,pl.name as level');
        if (trim($keyword)) {
            $query->andWhere(['LIKE', 'p.name', $keyword]);
        }

        $query->groupBy('p.id');
        $count = $query->count();
        $pagination = new Pagination(['totalCount' => $count]);
        $list = $query->orderBy('p.addtime DESC')
            ->limit($pagination->limit)
            ->offset($pagination->offset)
            ->asArray()
            ->all();




        foreach ($list as $i=>$item){
            $product_list=ProductList::find()->where(['user_id'=>$item['user_id'],'is_delete'=>0])->asArray()->all();
            $list[$i]['product_list']=$product_list;








        }



        return $this->render('photographers', [
            'list' => $list,
            'pagination' => $pagination,

        ]);
    }



    /**
     * 删除（逻辑）
     * @param int $id
     */
    public function actionPhotographerDel($id = 0)
    {
        $photographer = Photographer::findOne(['id' => $id, 'is_delete' => 0, 'store_id' => $this->store->id]);
        if (!$photographer) {
            return [
                'code' => 1,
                'msg' => '摄影师删除失败或已删除',
            ];
        }
        $photographer->is_delete = 1;
        if ($photographer->save()) {
            return [
                'code' => 0,
                'msg' => '成功',
            ];
        } else {
            foreach ($photographer->errors as $errors) {
                return [
                    'code' => 1,
                    'msg' => $errors[0],
                ];
            }
        }
    }

    //商品上下架
    public function actionPhotographerUpDown($id = 0, $type = 'down')
    {
        if ($type == 'down') {
            $photographer = Photographer::findOne(['id' => $id, 'is_delete' => 0, 'status' => 1, 'store_id' => $this->store->id]);
            if (!$photographer) {
                return [
                    'code' => 1,
                    'msg' => '摄影师已删除或未认证',
                ];
            }
            $photographer->status = 0;
            if ($photographer->save()) {
                return [
                    'code' => 0,
                    'msg' => '成功',
                ];
            } else {
                return [
                    'code' => 1,
                    'msg' => $photographer->errors,
                ];
            }
        } elseif ($type == 'up') {
            $photographer = Photographer::findOne(['id' => $id, 'is_delete' => 0, 'status' => 0, 'store_id' => $this->store->id]);
            if (!$photographer) {
                return [
                    'code' => 1,
                    'msg' => '摄影师已删除或已认证',
                ];
            }
            $photographer->status = 1;
            if ($photographer->save()) {
                return [
                    'code' => 0,
                    'msg' => '成功',
                ];
            } else {
                return [
                    'code' => 1,
                    'msg' => $photographer->errors,
                ];
            }
        }

    }


    /**
     * 设置商品排序
     */
    public function actionSetSort()
    {
        $form = new SetGoodsSortForm();
        $form->attributes = \Yii::$app->request->get();
        $form->store_id = $this->store->id;
        return $form->save();
    }

}