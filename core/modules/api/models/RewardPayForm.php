<?php
/**
 * Created by IntelliJ IDEA.
 * User: luwei
 * Date: 2017/7/18
 * Time: 12:11
 */

namespace app\modules\api\models;


use app\extensions\PinterOrder;
use app\extensions\SendMail;
use app\extensions\Sms;
use app\models\FormId;
use app\models\Goods;
use app\models\Order;
use app\models\OrderDetail;
use app\models\OrderMessage;
use app\models\OrderWarn;
use app\models\OrderUnion;
use app\models\Photographer;
use app\models\PrinterSetting;
use app\models\RequirementOrder;
use app\models\Reward;
use app\models\Setting;
use app\models\User;
use app\models\UserAccountLog;
use app\models\WechatTemplateMessage;
use app\models\WechatTplMsgSender;
use luweiss\wechat\Wechat;
use yii\helpers\VarDumper;

/**
 * @property User $user
 * @property Order $order
 */
class RewardPayForm extends Model
{
    public $store_id;
    public $order_id;
    public $user;
    /** @var  Wechat $wechat */
    private $wechat;
    private $order;




    public function rules()
    {
        return [
            [['order_id'], 'integer'],
        ];
    }


    public function search()
    {
        $this->wechat = $this->getWechat();
        if (!$this->validate())
            return $this->errorResponse;

        if ($this->order_id) {//单个订单付款
            $this->order = Reward::findOne([
                'store_id' => $this->store_id,
                'id' => $this->order_id,
            ]);




            if (!$this->order)
                return [
                    'code' => 1,
                    'msg' => '订单不存在',
                ];
            if ($this->order->is_delete == 1 || $this->order->is_cancel == 1) {
                return [
                    'code' => 1,
                    'msg' => '订单已取消'
                ];
            }

            $goods_names = '您支付了增加的服务费';
            $goods_names = mb_substr($goods_names, 0, 32, 'utf-8');
            $res = $this->unifiedOrder($goods_names);
            if (isset($res['code']) && $res['code'] == 1) {
                return $res;
            }
            //记录prepay_id发送模板消息用到
            FormId::addFormId([
                'store_id' => $this->store_id,
                'user_id' => $this->user->id,
                'wechat_open_id' => $this->user->wechat_open_id,
                'form_id' => $res['prepay_id'],
                'type' => 'prepay_id',
                'order_no' => $this->order->order_no,
            ]);

            $pay_data = [
                'appId' => $this->wechat->appId,
                'timeStamp' => '' . time(),
                'nonceStr' => md5(uniqid()),
                'package' => 'prepay_id=' . $res['prepay_id'],
                'signType' => 'MD5',
            ];
            $pay_data['paySign'] = $this->wechat->pay->makeSign($pay_data);
            return [
                'code' => 0,
                'msg' => 'success',
                'data' => (object)$pay_data,
                'res' => $res,
                'body' => $goods_names,
            ];

        }
    }


    //单个订单微信支付下单
    private function unifiedOrder($goods_names)
    {
        $res = $this->wechat->pay->unifiedOrder([
            'body' => $goods_names,
            'out_trade_no' => $this->order->order_no,
            'total_fee' => $this->order->price * 100,
            'notify_url' => \Yii::$app->request->hostInfo . \Yii::$app->request->baseUrl . '/pay-notify.php',
            'trade_type' => 'JSAPI',
            'openid' => $this->user->wechat_open_id,
        ]);




        if (!$res)
            return [
                'code' => 1,
                'msg' => '支付失败',
            ];
        if ($res['return_code'] != 'SUCCESS') {
            return [
                'code' => 1,
                'msg' => '支付失败，' . (isset($res['return_msg']) ? $res['return_msg'] : ''),
                'res' => $res,
            ];
        }
        if ($res['result_code'] != 'SUCCESS') {
            if ($res['err_code'] == 'INVALID_REQUEST') {//商户订单号重复
                $this->order->order_no = $this->getOrderNo();
                $this->order->save();
                return $this->unifiedOrder($goods_names);
            } else {
                return [
                    'code' => 1,
                    'msg' => '支付失败，' . (isset($res['err_code_des']) ? $res['err_code_des'] : ''),
                    'res' => $res,
                ];
            }
        }
        return $res;
    }

    private function getOrderNo()
    {
        $store_id = empty($this->store_id) ? 0 : $this->store_id;
        $order_no = null;
        while (true) {
            $order_no = "R".date('YmdHis') . rand(100000, 999999);
            $exist_order_no = Reward::find()->where(['order_no' => $order_no])->exists();
            if (!$exist_order_no)
                break;
        }
        return $order_no;
    }


}