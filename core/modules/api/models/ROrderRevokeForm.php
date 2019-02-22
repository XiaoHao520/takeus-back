<?php
/**
 * Created by IntelliJ IDEA.
 * User: luwei
 * Date: 2017/7/20
 * Time: 10:25
 */

namespace app\modules\api\models;


use app\extensions\SendMail;
use app\extensions\Sms;
use app\models\Coupon;
use app\models\Goods;
use app\models\Order;
use app\models\OrderDetail;
use app\models\Register;
use app\models\RequirementOrder;
use app\models\User;
use app\models\UserAccountLog;
use app\models\UserCoupon;
use app\models\UserTplMsgSender;
use app\models\WechatTplMsgSender;

class ROrderRevokeForm extends Model
{
    public $store_id;
    public $user_id;
    public $order_id;
    public $delete_pass = false;

    public function rules()
    {
        return [
            [['order_id'], 'required'],
        ];
    }

    public function save()
    {
        if (!$this->validate()) {
            return $this->errorResponse;
        }
        $order = RequirementOrder::findOne([
            'store_id' => $this->store_id,
            'user_id' => $this->user_id,
            'id' => $this->order_id,
            'is_delete' => 0,
            'is_cancel' => 0
        ]);
        if (!$order) {
            return [
                'code' => 1,
                'msg' => '订单不存在'
            ];
        }

        $t = \Yii::$app->db->beginTransaction();


        $user = User::findOne(['id' => $order->user_id]);


        $refund_price = $order->price;


        if ($order->coupon_id) {
            $userCoupon = UserCoupon::findOne($order->coupon_id);
            if ($userCoupon) {
                $userCoupon->is_use = 0;
                $coupon = Coupon::findOne($userCoupon->coupon_id);
                $refund_price -= $coupon->sub_price;
                $userCoupon->save();



            }
        }


        //已付款就退款
        if ($order->is_pay == 1) {
            $wechat = $this->getWechat();
            $data = [
                'out_trade_no' => $order->order_no,
                'out_refund_no' => $order->order_no,
                'total_fee' => $refund_price * 100,
                'refund_fee' => $refund_price * 100,
            ];





            $res = $wechat->pay->refund($data);
            if (!$res) {
                $t->rollBack();
                return [
                    'code' => 1,
                    'msg' => '订单取消失败，退款失败，服务端配置出错',
                ];
            }
            if ($res['return_code'] != 'SUCCESS') {
                $t->rollBack();
                return [
                    'code' => 1,
                    'msg' => '订单取消失败，退款失败，' . $res['return_msg'],
                    'res' => $res,
                ];
            }
            if ($res['result_code'] != 'SUCCESS') {
                $t->rollBack();
                return [
                    'code' => 1,
                    'msg' => '订单取消失败，退款失败，' . $res['err_code_des'],
                    'res' => $res,
                ];
            }
        }

        $order->is_cancel = 1;
        if ($order->save()) {
            $t->commit();
            $tpl_msg=new UserTplMsgSender($this->store_id,$this->user_id,$order->id,$this->getWechat());
            $tpl_msg->revokeMsg();
            return [
                'code' => 0,
                'msg' => '退款成功',

            ];
        } else {
            $t->rollBack();
            return [
                'code' => 1,
                'msg' => '订单取消失败'
            ];
        }
    }
}