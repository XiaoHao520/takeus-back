<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/8
 * Time: 14:53
 */

namespace app\modules\mch\controllers;


use app\extensions\Sms;
use app\hejiang\ApiResponse;
use app\hejiang\ValidationErrorResponse;
use app\models\Cash;
use app\models\CashWechatTplSender;
use app\models\Color;
use app\models\Option;
use app\models\Qrcode;
use app\models\Setting;
use app\models\Share;
use app\models\User;
use app\models\WechatTemplateMessage;
use app\models\WechatTplMsgSender;
use app\modules\mch\models\CashForm;
use app\modules\mch\models\OrderListForm;
use app\modules\mch\models\QrcodeForm;
use app\modules\mch\models\ShareBasicForm;
use app\modules\mch\models\ShareCustomForm;
use app\modules\mch\models\ShareListForm;
use app\modules\mch\models\ShareOrderForm;
use app\modules\mch\models\ShareSettingForm;
use app\modules\mch\models\StoreDataForm;
use yii\helpers\VarDumper;

class ShareController extends Controller
{
    /**
     * @return string
     * 分销商列表
     */
    public function actionIndex()
    {
        $form = new ShareListForm();
        $form->attributes = \Yii::$app->request->get();
        $form->store_id = $this->store->id;
        $form->limit = 10;
        $arr = $form->getList();
        $team = $arr['team_list'];
        $count = $form->getCount();
        $setting = Setting::findOne(['store_id' => $this->store->id]);
        return $this->render('index', [
            'list' => $arr['list'],
            'pagination' => $arr['pagination'],
            'setting' => $setting,
            'team' => \Yii::$app->serializer->encode($team),
            'count' => $count
        ]);
    }

//    添加备注
    public function actionSellerComments()
    {
        $user_id = \Yii::$app->request->get('user_id');
        $seller_comments = \Yii::$app->request->get('seller_comments');
        $share = Share::findOne(['store_id' => $this->store->id, 'is_delete' => 0, 'id' => $user_id]);
        $share->seller_comments = $seller_comments;
        if ($share->save()) {
            return [
                'code' => 0,
                'msg' => '成功'
            ];
        } else {
            return new ValidationErrorResponse($share->errors);
        }
    }

    /**
     * @return mixed|string
     * 佣金设置
     */
    public function actionSetting()
    {
        $store_id = $this->store->id;
        $list = Setting::findOne(['store_id' => $store_id]);
        if (!$list)
            $list = new Setting();
        if (\Yii::$app->request->isPost) {
            $form = new ShareSettingForm();
            $model = \Yii::$app->request->post('model');
            $form->list = $list;
            $form->store_id = $store_id;
            $form->attributes = $model;
            return $form->save();
        }
        foreach ($list as $index => $value) {
            $list[$index] = str_replace("\"", "&quot;", $value);
        }
        return $this->render('setting', [
            'list' => $list
        ]);
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
        $goods_user_group = [];
        foreach ($goods_group as $index => $value) {
            if ($get['type'] == 0) {
                array_push($goods_id_group, $value['id']);
                array_push($goods_user_group, $value['user_id']);
            }
        }
        $condition = ['and', ['in', 'id', $goods_id_group], ['store_id' => $this->store->id]];
        $usercondition = ['and', ['in', 'id', $goods_user_group], ['store_id' => $this->store->id]];

        if ($get['type'] == 0) {
            $transaction = \Yii::$app->db->beginTransaction();
            try {
                $res = Share::updateAll(['status' => 1], $condition);
                $user = User::updateAll(['time' => time(), 'is_distributor' => 1], $usercondition);
                $transaction->commit();
            } catch (\Exception $e) {
                // $error = $e->getMessae();
                $transaction->rollback();
            }
        }

        if ($res > 0) {
            return [
                'code' => 0,
                'msg' => 'success'
            ];
        } else {
            return [
                'code' => 1,
                'msg' => 'fail'
            ];
        }
    }

    /**
     * @return mixed|string
     * 基础设置
     */
    public function actionBasic()
    {
        $store_id = $this->store->id;
        $list = Setting::findOne(['store_id' => $store_id]);
        $qrcode = Qrcode::findOne(['store_id' => $store_id, 'is_delete' => 0]);
        if (!$list)
            $list = new Setting();
        if (!$qrcode) {
            $qrcode = new Qrcode();
        }
        if (\Yii::$app->request->isPost) {
            $model = \Yii::$app->request->post('model');
            $form = new ShareBasicForm();
            $form->list = $list;
            $form->store_id = $store_id;
            $form->attributes = $model;
            return $form->save();
        }
        $option = Option::getList('cash_max_day,auto_share_val', $this->store->id, 'share', 0.00);
        $tpl_msg = Option::getList('cash_success_tpl,cash_fail_tpl,apply_tpl', $this->store->id, 'share', '');
        return $this->render('basic', [
            'list' => $list,
            'qrcode' => $qrcode,
            'option' => $option,
            'tpl_msg' => $tpl_msg
        ]);
    }

    /**
     * @param int $id
     * @param int $status
     * @return mixed|string
     * 申请审核
     */
    public function actionStatus($id = 0, $status = 1)
    {
        $share = Share::findOne(['id' => $id, 'is_delete' => 0, 'store_id' => $this->store->id]);
        if (!$share) {
            return [
                'code' => 1,
                'msg' => '网络异常，请刷新重试'
            ];
        }
        if (!in_array($status, [1, 2])) {
            return [
                'code' => 1,
                'msg' => '网络异常，请刷新重试'
            ];
        }
        $share->status = $status;
        if ($status == 1) {
            User::updateAll(['time' => time(), 'is_distributor' => 1], ['id' => $share->user_id, 'store_id' => $this->store->id]);
        } else {
            User::updateAll(['time' => time(), 'is_distributor' => 0], ['id' => $share->user_id, 'store_id' => $this->store->id]);
        }
        if ($share->save()) {
            $tpl = new CashWechatTplSender($this->store->id, $share->id, $this->wechat, 1);
            $tpl->applyMsg();
            return [
                'code' => 0,
                'msg' => '成功'
            ];
        } else {
            return [
                'code' => 1,
                'msg' => '请刷新重试'
            ];
        }
    }

    /**
     * @return string
     * 提现列表
     */
    public function actionCash()
    {
        $form = new CashForm();
        $form->attributes = \Yii::$app->request->get();
        $form->store_id = $this->store->id;
        $form->limit = 10;
        $arr = $form->getList();
        $count = $form->getCount();
        return $this->render('cash', [
            'list' => $arr[0],
            'pagination' => $arr[1],
            'count' => $count
        ]);
    }

    /**
     * @param int $id
     * @param int $status
     * @return mixed|string
     * 申请审核
     */
    public function actionApply($id = 0, $status = 0)
    {
        $cash = Cash::findOne(['id' => $id, 'is_delete' => 0, 'store_id' => $this->store->id]);
        if (!$cash) {
            return [
                'code' => 1,
                'msg' => '提现记录不存在，请刷新重试'
            ];
        }
        $cash_cache = \Yii::$app->cache->get('cash_cache_' . $id);
        if ($cash_cache && $cash_cache == $cash->order_no) {
            return [
                'code' => 1,
                'msg' => '网络繁忙，请刷新重试'
            ];
        }
        if (!$cash->order_no) {
            $order_no = null;
            while (true) {
                $order_no = date('YmdHis') . rand(100000, 999999);
                $exist_order_no = Cash::find()->where(['order_no' => $order_no])->exists();
                if (!$exist_order_no)
                    break;
            }
            $cash->order_no = $order_no;
            $cash->save();
        }
        \Yii::$app->cache->set('cash_cache_' . $id, $cash->order_no);
        if (!in_array($status, [1, 3])) {
            return [
                'code' => 1,
                'msg' => '提现记录已审核，请刷新重试'
            ];
        }
        $cash->status = $status;
        if ($status == 3) {
            $user = User::findOne(['id' => $cash->user_id]);
            $user->price += $cash->price;
            if (!$user->save()) {
                return [
                    'code' => 1,
                    'msg' => '网络异常'
                ];
            }
        }
        \Yii::$app->cache->set('cash_cache_' . $id, false);
        if ($cash->save()) {
            if ($cash->status == 3) {
                $wechat_tpl_meg_sender = new CashWechatTplSender($this->store->id, $cash->id, $this->wechat);
                $wechat_tpl_meg_sender->cashFailMsg();
            }
            return [
                'code' => 0,
                'msg' => '成功'
            ];
        } else {
            return [
                'code' => 1,
                'msg' => '网络异常,请刷新重试'
            ];
        }
    }

//    余额提现
    public function actionApplyRemaining($id = 0, $status = 0)
    {
        return 1111111111;
        $cash = Cash::findOne(['id' => $id, 'is_delete' => 0, 'store_id' => $this->store->id]);
        if (!$cash) {
            return [
                'code' => 1,
                'msg' => '提现记录不存在，请刷新重试'
            ];
        }
        if (!in_array($status, [5])) {
            return [
                'code' => 1,
                'msg' => '提现记录已审核，请刷新重试'
            ];
        }

    }

    /**
     * @param int $id
     * @param int $status
     * @return mixed|string
     * 确认打款
     * 支付未做
     */
    public function actionConfirm($id = 0, $status = 0)
    {
        $cash = Cash::findOne(['id' => $id, 'is_delete' => 0, 'store_id' => $this->store->id]);
        if (!$cash) {
            return [
                'code' => 1,
                'msg' => '提现记录不存在，请刷新重试'
            ];
        }
        $cash_cache = \Yii::$app->cache->get('cash_cache_' . $id);
        if ($cash_cache && $cash_cache == $cash->order_no) {
            return [
                'code' => 1,
                'msg' => '网络繁忙，请刷新重试'
            ];
        }
        if (!$cash->order_no) {
            $order_no = null;
            while (true) {
                $order_no = date('YmdHis') . rand(100000, 999999);
                $exist_order_no = Cash::find()->where(['order_no' => $order_no])->exists();
                if (!$exist_order_no)
                    break;
            }
            $cash->order_no = $order_no;
            $cash->save();
        }
        \Yii::$app->cache->set('cash_cache_' . $id, $cash->order_no);
        if ($cash->status != 1) {
            return [
                'code' => 1,
                'msg' => '操作错误，请刷新重试'
            ];
        }
        if ($status == 2) {  //微信自动打款
            $cash->status = 2;
            $cash->pay_time = time();
            $cash->pay_type = 1;
            $user = User::findOne(['id' => $cash->user_id]);
            $data = [
                'partner_trade_no' => $cash->order_no,
                'openid' => $user->wechat_open_id,
                'amount' => $cash->price * 100,
                'desc' => '转账'
            ];
            $res = $this->wechat->pay->transfers($data);
        } else if ($status == 4) { //手动打款
            $cash->status = 2;
            $cash->pay_time = time();
            $cash->pay_type = 2;
            if ($cash->type == 3) {
                $user = User::findOne(['id' => $cash->user_id]);
                $user->money += doubleval($cash->price);
                if (!$user->save()) {
                    foreach ($user->errors as $error) {
                        return [
                            'code' => 1,
                            'msg' => $error
                        ];
                    }
                }
            }
//            $cash->type = 2;
            $res['result_code'] = "SUCCESS";
        }
        \Yii::$app->cache->set('cash_cache_' . $id, false);
        if ($res['result_code'] == 'SUCCESS') {
            $cash->save();
            $wechat_tpl_meg_sender = new CashWechatTplSender($this->store->id, $cash->id, $this->wechat);
            $wechat_tpl_meg_sender->cashMsg();
            return [
                'code' => 0,
                'msg' => '成功'
            ];
        } else {
            return [
                'code' => 1,
                'msg' => $res['err_code_des'],
                'data' => $res
            ];
        }
    }

    /**
     * @return string
     * 设置推广海报
     */
    public function actionQrcode()
    {
        $store_id = $this->store->id;
        $qrcode = Qrcode::findOne(['store_id' => $store_id, 'is_delete' => 0]);
        $color = Color::find()->select('id,color')->andWhere(['is_delete' => 0])->asArray()->all();
        if (!$qrcode) {
            $qrcode = new Qrcode();
        }
        if (\Yii::$app->request->isPost) {
            $form = new QrcodeForm();
            $model = \Yii::$app->request->post('model');
            $form->store_id = $store_id;
            $form->qrcode = $qrcode;
            $form->attributes = $model;
            return $form->save();
        }
        $font_position = json_decode($qrcode->font_position, true);
        $qrcode_position = json_decode($qrcode->qrcode_position, true);
        $avatar_position = json_decode($qrcode->avatar_position, true);
        $avatar_size = json_decode($qrcode->avatar_size, true);
        $qrcode_size = json_decode($qrcode->qrcode_size, true);
        $font_size = json_decode($qrcode->font, true);
        $first = Color::findOne(['id' => $font_size['color']]);
        return $this->render('qrcode', [
            'qrcode' => $qrcode,
            'color' => \Yii::$app->serializer->encode($color),
            'first' => $first->id,
            'font_c' => $first->color,
            'avatar_w' => $avatar_size['w'],
            'avatar_x' => $avatar_position['x'],
            'avatar_y' => $avatar_position['y'],
            'qrcode_w' => $qrcode_size['w'],
            'qrcode_c' => ($qrcode_size['c'] == 'true') ? 1 : 0,
            'qrcode_x' => $qrcode_position['x'],
            'qrcode_y' => $qrcode_position['y'],
            'font_x' => $font_position['x'],
            'font_y' => $font_position['y'],
            'font_w' => $font_size['size'],
        ]);
    }

    /**
     * @param int $id
     * @return mixed|string
     * @throws \yii\db\Exception
     * 删除分销商
     */
    public function actionDel($id = 0)
    {
        $share = Share::findOne(['id' => $id, 'is_delete' => 0]);
        if (!$share) {
            return [
                'code' => 1,
                'msg' => '网络异常'
            ];
        }
        $t = \Yii::$app->db->beginTransaction();
        $count1 = Share::updateAll(['is_delete' => 1], 'id=:id', [':id' => $id]);
        $count2 = User::updateAll(['is_distributor' => 0, 'parent_id' => 0, 'time' => 0], 'id=:id', [':id' => $share->user_id]);
        $count3 = User::updateAll(['parent_id' => 0], 'parent_id=:parent_id', [':parent_id' => $share->user_id]);
        if ($count1 != 0) {
            $t->commit();
            return [
                'code' => 0,
                'msg' => '成功'
            ];
        } else {
            $t->rollBack();
            return [
                'code' => 1,
                'msg' => '网络异常'
            ];
        }

    }

    public function actionTest()
    {
//        return $this->render('/tpl.v2.php');
//        $res = Sms::send($this->store->id, "20170909152136642340");
//        var_dump($res);
//        exit();
    }

    public function actionOrder()
    {
        $parent_id = \Yii::$app->request->get('parent_id');
        $form = new ShareOrderForm();
        $form->attributes = \Yii::$app->request->get();
        $form->store_id = $this->store->id;
        $form->parent_id = $parent_id ? (int)$parent_id : false;
        $form->limit = 10;
        $data = $form->search();
        if ($parent_id) {
            $user = User::findOne(['store_id' => $this->store->id, 'id' => $parent_id]);
        }
        $setting = Setting::findOne(['store_id' => $this->store->id]);
        return $this->render('order', [
            'row_count' => $data['row_count'],
            'pagination' => $data['pagination'],
            'list' => $data['list'],
            //'count_data' => OrderListForm::getCountData($this->store->id),
//            'store_data' => $store_data_form->search(),
            'user' => $user,
            'setting' => $setting
        ]);
    }

    public function actionCustom()
    {
        $form = new ShareCustomForm();
        $form->store_id = $this->store->id;
        if (\Yii::$app->request->isPost) {
            $form->attributes = \Yii::$app->request->post();
            return $form->saveData();
        } else {
            if (\Yii::$app->request->isAjax) {
                return $form->getData();
            } else {
                return $this->render('custom');
            }
        }
    }
}