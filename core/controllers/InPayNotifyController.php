<?php
/**
 * Created by PhpStorm.
 * User: zc
 * Date: 2018/5/21
 * Time: 14:30
 */

namespace app\controllers;


use luweiss\wechat\DataTransform;
use app\models\IntegralCouponOrder;
use app\models\Coupon;
use app\models\User;
use app\models\UserCoupon;
use app\models\IntegralOrder;
use app\models\IntegralOrderDetail;
use app\models\IntegralGoods;
use app\models\Register;
class InPayNotifyController extends Controller
{
    public $enableCsrfValidation = false;

    public function actionIndex()
    {
        $xml = file_get_contents("php://input");
        $res = DataTransform::xmlToArray($xml);
        if ($res && !empty($res['out_trade_no'])) {//微信支付回调
            $this->wechatPayNotify($res);
        }
    }

    private function wechatPayNotify($res)
    {
        if ($res['result_code'] != 'SUCCESS' && $res['return_code'] != 'SUCCESS')
            return;
        $orderNoHead = substr($res['out_trade_no'],0,1);
        if ($orderNoHead == 'G'){
            // 商品购买回调
            return $this->goodsOrderNotify($res);
        }
        $icOrder = IntegralCouponOrder::findOne(['order_no' => $res['out_trade_no']]);
        $user = User::findOne(['id' => $icOrder->user_id]);
        $icOrder->is_pay = 1;
        $icOrder->pay_time = time();
        $icOrder->save();
        $coupon = Coupon::findOne(['id' => $icOrder->coupon_id]);
        $coupon->total_num -= 1;
        $coupon->save();
        $new_coupon = new Coupon();
        $new_coupon->attributes = $coupon->attributes;
        $new_coupon->is_delete = 1;
        $new_coupon->save();
        $userCoupon = new UserCoupon();
        $userCoupon->store_id = $icOrder->store_id;
        $userCoupon->user_id = $icOrder->user_id;
        $userCoupon->coupon_id = $new_coupon->id;
        $userCoupon->coupon_auto_send_id = 0;
        $userCoupon->is_expire = 0;
        $userCoupon->is_use = 0;
        $userCoupon->is_delete = 0;
        $userCoupon->addtime = time();
        $userCoupon->type = 3;
        $userCoupon->price = $coupon->price;
        $userCoupon->integral = $coupon->integral;
        if ($coupon->expire_type == 1) {
            $userCoupon->begin_time = time();
            $userCoupon->end_time = time() + max(0, 86400 * $coupon->expire_day);
        } elseif ($coupon->expire_type == 2) {
            $userCoupon->begin_time = $coupon->begin_time;
            $userCoupon->end_time = $coupon->end_time;
        }
        $userCoupon->save();
        $user->integral -= $coupon->integral;
        $user->save();
        $register = new Register();
        $register->store_id = $icOrder->store_id;
        $register->user_id = $user->id;
        $register->register_time = '..';
        $register->addtime = time();
        $register->continuation = 0;
        $register->type = 10;
        $register->integral = $coupon->integral;
        $register->order_id = $icOrder->id;
        $register->save();
        echo '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
        return;
    }

    private function goodsOrderNotify($res)
    {
        $order = IntegralOrder::findOne(['order_no' => $res['out_trade_no']]);
        $order->is_pay = 1;
        $order->pay_time = time();
        $order->pay_type = 1;
        $order->save();
        $user = User::findOne(['id' => $order->user_id]);
        $user->integral -= $order->integral;
        $user->save();
        $register = new Register();
        $register->store_id = $order->store_id;
        $register->user_id = $user->id;
        $register->register_time = '..';
        $register->addtime = time();
        $register->continuation = 0;
        $register->type = 11;
        $register->integral = '-'.$order->integral;
        $register->order_id = $order->id;
        $register->save();
        echo '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
        return;
    }
}