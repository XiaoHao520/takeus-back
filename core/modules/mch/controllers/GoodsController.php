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
use app\models\PostageRules;
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
class GoodsController extends Controller
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
    public function actionGoods($keyword = null, $status = null)
    {
        $query_cat = GoodsCat::find()->alias('gc')->leftJoin(['c' => Cat::tableName()], 'c.id=gc.cat_id')
            ->where(['gc.store_id' => $this->store->id, 'gc.is_delete' => 0])->select('gc.goods_id,c.name,gc.cat_id');
        $query = Goods::find()->alias('g')->where(['g.store_id' => $this->store->id, 'g.is_delete' => 0, 'g.mch_id' => 0]);

        if ($status != null) {
            $query->andWhere('g.status=:status', [':status' => $status]);
        }
        $query->leftJoin(['c' => Cat::tableName()], 'c.id=g.cat_id');
        $query->leftJoin(['gc' => $query_cat], 'gc.goods_id=g.id');

        $cat_query = clone $query;

        $query->select('g.id,g.name,g.price,g.original_price,g.status,g.cover_pic,g.sort,g.attr,g.cat_id,g.virtual_sales,g.store_id,g.quick_purchase');
        if (trim($keyword)) {
            $query->andWhere(['LIKE', 'g.name', $keyword]);
        }
        if (isset($_GET['cat'])) {
            $cat = trim($_GET['cat']);
//            $query->andWhere([
            //                'or',
            //                ['like', 'c.name', $cat],
            //                ['like','gc.name',$cat]
            //            ]);
            $query->andWhere([
                'or',
                ['c.name' => $cat],
                ['gc.name' => $cat],
            ]);
        }
        $cat_list = $cat_query->groupBy('name')->orderBy(['g.cat_id' => SORT_ASC])->select([
            '(case when g.cat_id=0 then gc.name else c.name end) name',
        ])->asArray()->column();
        $query->groupBy('g.id');
        $count = $query->count();
        $pagination = new Pagination(['totalCount' => $count]);
        $list = $query->orderBy('g.sort ASC,g.addtime DESC')
            ->limit($pagination->limit)
            ->offset($pagination->offset)
            ->with(['goodsPicList', 'catList1.cat', 'cat'])
            ->all();
        return $this->render('goods', [
            'list' => $list,
            'pagination' => $pagination,
            'cat_list' => $cat_list,
        ]);
    }

    // 后台商品小程序码
    public function actionGoodsQrcode()
    {
        $form = new GoodsQrcodeForm();
        $form->attributes = \Yii::$app->request->get();
        $form->store_id = $this->store_id;
        if (!\Yii::$app->user->isGuest) {
            $form->user_id = \Yii::$app->user->id;
        }
        return $form->search();
    }

    /**
     * 商品修改
     * @param int $id
     * @return string
     */
    public function actionGoodsEdit($id = 0)
    {
        $goods = Goods::findOne(['id' => $id, 'store_id' => $this->store->id, 'mch_id' => 0]);
        if (!$goods) {
            $goods = new Goods();
        }
        $form = new GoodsForm();
        if (\Yii::$app->request->isPost) {
            $model = \Yii::$app->request->post('model');
            if ($model['quick_purchase'] == 0) {
                $model['hot_cakes'] = 0;
            }
            $model['store_id'] = $this->store->id;
            $form->attributes = $model;
            $form->attr = \Yii::$app->request->post('attr');
            $form->goods_card = \Yii::$app->request->post('goods_card');
            $form->full_cut = \Yii::$app->request->post('full_cut');
            $form->integral = \Yii::$app->request->post('integral');
            $form->goods = $goods;
            return $form->save();
        }

        $cat_list = Cat::find()->where(['store_id' => $this->store->id, 'is_delete' => 0, 'parent_id' => 0])->all();
        $postageRiles = PostageRules::find()->where(['store_id' => $this->store->id, 'is_delete' => 0])->all();
        $card_list = Card::find()->where(['store_id' => $this->store->id, 'is_delete' => 0])->asArray()->all();
        if ($goods->full_cut) {
            $goods->full_cut = json_decode($goods->full_cut, true);
        } else {
            $goods->full_cut = [
                'pieces' => '',
                'forehead' => '',
            ];
        }
        if ($goods->integral) {
            $goods->integral = json_decode($goods->integral, true);
        } else {
            $goods->integral = [
                'give' => 0,
                'deduction' => 0,
                'more' => 0,
            ];
        }
        $goods_card_list = Goods::getGoodsCard($goods->id);
        $goods_cat_list = Goods::getCatList($goods);
        foreach ($goods as $index => $value) {
            if (in_array($index, ['attr', 'full_cat', 'integral', 'payment', 'detail'])) {
                continue;
            }
            if(is_array($value) || is_object($value)){
                continue;
            }
            $goods[$index] = str_replace("\"", "&quot;", $value);
        }
        return $this->render('goods-edit', [
            'goods' => $goods,
            'cat_list' => $cat_list,
            'postageRiles' => $postageRiles,
            'card_list' => \Yii::$app->serializer->encode($card_list),
            'goods_card_list' => \Yii::$app->serializer->encode($goods_card_list),
            'goods_cat_list' => \Yii::$app->serializer->encode($goods_cat_list),
        ]);
    }

    /**
     * 删除（逻辑）
     * @param int $id
     */
    public function actionGoodsDel($id = 0)
    {
        $goods = Goods::findOne(['id' => $id, 'is_delete' => 0, 'store_id' => $this->store->id]);
        if (!$goods) {
            return [
                'code' => 1,
                'msg' => '商品删除失败或已删除',
            ];
        }
        $goods->is_delete = 1;
        if ($goods->save()) {
            return [
                'code' => 0,
                'msg' => '成功',
            ];
        } else {
            foreach ($goods->errors as $errors) {
                return [
                    'code' => 1,
                    'msg' => $errors[0],
                ];
            }
        }
    }

    //商品上下架
    public function actionGoodsUpDown($id = 0, $type = 'down')
    {
        if ($type == 'down') {
            $goods = Goods::findOne(['id' => $id, 'is_delete' => 0, 'status' => 1, 'store_id' => $this->store->id]);
            if (!$goods) {
                return [
                    'code' => 1,
                    'msg' => '商品已删除或已下架',
                ];
            }
            $goods->status = 0;
        } elseif ($type == 'up') {
            $goods = Goods::findOne(['id' => $id, 'is_delete' => 0, 'status' => 0, 'store_id' => $this->store->id]);

            if (!$goods) {
                return [
                    'code' => 1,
                    'msg' => '商品已删除或已上架',
                ];
            }
            if (!$goods->getNum()) {
                $return_url = \Yii::$app->urlManager->createUrl(['mch/goods/goods-edit', 'id' => $goods->id]);
                if (!$goods->use_attr) {
                    $return_url = \Yii::$app->urlManager->createUrl(['mch/goods/goods-edit', 'id' => $goods->id]) . '#step3';
                }

                return [
                    'code' => 1,
                    'msg' => '商品库存不足，请先完善商品库存',
                    'return_url' => $return_url,
                ];
            }
            $goods->status = 1;
        } elseif ($type == 'start') {
            $goods = Goods::findOne(['id' => $id, 'is_delete' => 0, 'store_id' => $this->store->id]);

            if (!$goods) {
                return [
                    'code' => 1,
                    'msg' => '商品已删除或已加入',
                ];
            }
            $goods->quick_purchase = 1;
        } elseif ($type == 'close') {
            $goods = Goods::findOne(['id' => $id, 'is_delete' => 0, 'store_id' => $this->store->id]);

            if (!$goods) {
                return [
                    'code' => 1,
                    'msg' => '商品已删除或已关闭',
                ];
            }
            $goods->quick_purchase = 0;
        } else {
            return [
                'code' => 1,
                'msg' => '参数错误',
            ];
        }
        if ($goods->save()) {
            return [
                'code' => 0,
                'msg' => '成功',
            ];
        } else {
            foreach ($goods->errors as $errors) {
                return [
                    'code' => 1,
                    'msg' => $errors[0],
                ];
            }
        }
    }

    /**
     * 商品规格库存管理
     * @param int $id 商品id
     */
    public function actionGoodsAttr($id)
    {
        $goods = Goods::findOne([
            'store_id' => $this->store->id,
            'is_delete' => 0,
            'id' => $id,
        ]);
        if (!$goods) {
            throw new HttpException(404);
        }

        if (\Yii::$app->request->isPost) {
            $goods->attr = \Yii::$app->serializer->encode(\Yii::$app->request->post('attr', []));
//            var_dump($goods->attr);die();
            if ($goods->save()) {
                return [
                    'code' => 0,
                    'msg' => '保存成功',
                ];
            } else {
                return [
                    'code' => 1,
                    'msg' => '保存失败',
                ];
            }
        } else {
            $attr_group_list = AttrGroup::find()
                ->select('id attr_group_id,attr_group_name')
                ->where(['store_id' => $this->store->id, 'is_delete' => 0])
                ->asArray()->all();
            foreach ($attr_group_list as $i => $g) {
                $attr_list = Attr::find()
                    ->select('id attr_id,attr_name')
                    ->where(['attr_group_id' => $g['attr_group_id'], 'is_delete' => 0, 'is_default' => 0])
                    ->asArray()->all();
                if (empty($attr_list)) {
                    unset($attr_group_list[$i]);
                } else {
                    $goods_attr_list = json_decode($goods->attr, true);
                    if (!$goods_attr_list) {
                        $goods_attr_list = [];
                    }

                    foreach ($attr_list as $j => $attr) {
                        $checked = false;
                        foreach ($goods_attr_list as $goods_attr) {

                            foreach ($goods_attr['attr_list'] as $g_attr) {
                                if ($attr['attr_id'] == $g_attr['attr_id']) {
                                    $checked = true;
                                    break;
                                }
                            }
                            if ($checked) {
                                break;
                            }

                        }
                        $attr_list[$j]['checked'] = $checked;
                    }
                    $attr_group_list[$i]['attr_list'] = $attr_list;
                }
            }
            $new_attr_group_list = [];
            foreach ($attr_group_list as $item) {
                $new_attr_group_list[] = $item;
            }

            return $this->render('goods-attr', [
                'goods' => $goods,
                'attr_group_list' => $new_attr_group_list,
                'checked_attr_list' => $goods->attr,
            ]);
        }
    }

    /**
     * 一键采集
     */
    public function actionCopy()
    {
        $form = new CopyForm();
        $form->attributes = \Yii::$app->request->get();
        return $form->copy();
    }

    /**
     * 淘宝采集
     */
    public function actionTcopy()
    {
        $form = new CopyForm();
        $html = \Yii::$app->request->post('html');
        return $form->t_copy_2($html);
    }

    /**
     * 批量设置
     */
    public function actionBatch()
    {
        $get = \Yii::$app->request->get();
        $res = 0;
        $goods_group = $get['goods_group'];
        $goods_id_group = [];
        foreach ($goods_group as $index => $value) {
            if ($get['type'] == 0) {
                if ($value['num'] != 0) {
                    array_push($goods_id_group, $value['id']);
                }
            } else {
                array_push($goods_id_group, $value['id']);
            }
        }

        $condition = ['and', ['in', 'id', $goods_id_group], ['store_id' => $this->store->id]];
        if ($get['type'] == 0) { //批量上架
            $res = Goods::updateAll(['status' => 1], $condition);
        } elseif ($get['type'] == 1) { //批量下架
            $res = Goods::updateAll(['status' => 0], $condition);
        } elseif ($get['type'] == 2) { //批量删除
            $res = Goods::updateAll(['is_delete' => 1], $condition);
        } elseif ($get['type'] == 3) { //批量加入快速购买
            $res = Goods::updateAll(['quick_purchase' => 1], $condition);
        } elseif ($get['type'] == 4) { //批量关闭快速购买
            $res = Goods::updateAll(['quick_purchase' => 0], $condition);
        }
        if ($res > 0) {
            return [
                'code' => 0,
                'msg' => '设置成功',
            ];
        } else {
            return [
                'code' => 1,
                'msg' => '请刷新重试',
            ];
        }
    }

    /**
     * 批量设置积分
     */
    public function actionBatchIntegral()
    {
        $get = \Yii::$app->request->get();
        $integral['give'] = $get['give'] ?: 0;
        $integral['forehead'] = $get['forehead'] ?: 0;
        $integral['more'] = $get['more'] ?: 0;

        $integral = \Yii::$app->serializer->encode($integral);

        if (empty($get['goods_group'])) {
            return [
                'code' => 1,
                'msg' => '请选择商品',
            ];
        }
        $res = Goods::updateAll(['integral' => $integral], ['in', 'id', $get['goods_group']]);
        if ($res) {
            return [
                'code' => 0,
                'msg' => 'success',
            ];
        } else {
            return [
                'code' => 1,
                'msg' => '系统错误',
            ];
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