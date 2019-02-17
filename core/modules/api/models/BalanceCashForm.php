<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/11
 * Time: 9:53
 */

namespace app\modules\api\models;

use app\models\BalanceCash;
use app\models\FormId;
use app\models\Setting;
use app\models\Store;
use app\models\User;

class BalanceCashForm extends Model
{
    public $user_id;
    public $store_id;
    public $cash;
    public $form_id;
    public $name;
    public $mobile;


    public function rules()
    {
        return [
            [['cash'], 'required'],
            [['name'], 'required', 'on' => 'CASH'],
            [['cash'], 'number', 'min' => 0],
            [['form_id'], 'trim'],
             [['mobile'],'string']
        ];
    }

    public function attributeLabels()
    {
        return [
            'cash' => '提现金额',

        ];
    }

    public function save()
    {
        if ($this->validate()) {


            $store = Store::findOne($this->store_id);
            if (!$store) {
                return [
                    'code' => 1,
                    'msg' => '网络异常'
                ];
            }
            $user = User::findOne(['id' => $this->user_id, 'store_id' => $this->store_id]);
            if (!$user) {
                return [
                    'code' => 1,
                    'msg' => '网络异常'
                ];
            }
            $share_setting = Setting::findOne(['store_id' => $this->store_id]);
            if ($this->cash < $share_setting->min_money) {
                return [
                    'code' => 1,
                    'msg' => '提现金额不能小于' . $share_setting->min_money . '元'
                ];
            }
            if ($user->money < $this->cash) {
                return [
                    'code' => 1,
                    'msg' => '提现金额不能超过剩余金额'
                ];
            }
            $exit = BalanceCash::find()->andWhere(['=', 'status', 0])->andWhere(['user_id' => $this->user_id, 'store_id' => $this->store_id])->exists();
            if ($exit) {
                return [
                    'code' => 1,
                    'msg' => '尚有未完成的提现申请'
                ];
            }
            $t = \Yii::$app->db->beginTransaction();
            $balance_cash = new BalanceCash();
            $balance_cash->price = $this->cash;
            $balance_cash->real_price = $this->cash;
            $balance_cash->order_no = $this->getOrderNo();
            $balance_cash->is_delete = 0;
            $balance_cash->status = 0;
            $balance_cash->addtime = time();
            $balance_cash->user_id = $this->user_id;
            $balance_cash->store_id = $this->store_id;
            $balance_cash->pay_time = 0;
            $balance_cash->name = $this->name;
            $balance_cash->mobile = $this->mobile;

            if ($balance_cash->save()) {
                FormId::addFormId([
                    'form_id' => $this->form_id,
                    'store_id' => $this->store_id,
                    'wechat_open_id' => $user->wechat_open_id,
                    'order_no' => 'cash' . md5("id={$balance_cash->id}&store_id={$this->store_id}"),
                    'type' => 'form_id',
                    'send_count' => 0,
                    'user_id' => $user->id
                ]);
                $user->money -= $this->cash;
                if (!$user->save()) {
                    $t->rollBack();
                    return [
                        'code' => 1,
                        'msg' => '网络异常'
                    ];
                }
                $t->commit();
                return [
                    'code' => 0,
                    'msg' => '申请成功'
                ];
            } else {
                $t->rollBack();
                return [
                    'code' => 1,
                    'msg' => '网络异常',
                    'data' => $balance_cash
                ];
            }
        } else {
            return $this->errorResponse;
        }
    }

    private function getOrderNo()
    {
        $order_no = null;
        while (true) {
            $order_no = date('YmdHis') . rand(100000, 999999);
            $exist_order_no = BalanceCash::find()->where(['order_no' => $order_no])->exists();
            if (!$exist_order_no)
                break;
        }
        return $order_no;
    }
}