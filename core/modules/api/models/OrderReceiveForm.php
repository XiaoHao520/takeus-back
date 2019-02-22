<?php
/**
 * Created by IntelliJ IDEA.
 * User: luwei
 * Date: 2017/7/24
 * Time: 18:42
 */

namespace app\modules\api\models;


use app\models\FormId;
use app\models\Goods;
use app\models\Order;
use app\models\OrderDetail;
use app\models\Store;
use app\models\User;
use app\models\WechatTemplateMessage;
use app\models\WechatTplMsgSender;

class OrderReceiveForm extends Model
{
    public $store_id;
    public $order_id;



    public function save()
    {

        try {
            $wechat_tpl_meg_sender = new WechatTplMsgSender($this->store_id, $this->order_id,$this->getWechat());
            $wechat_tpl_meg_sender->sendReceiveMsg();
        } catch (\Exception $e) {
            \Yii::warning($e->getMessage());
              var_dump($e->getMessage());
              die;
        }
    }

    /**
     * @deprecated 已废弃
     */
    private function sendMessage($order)
    {
        $user = User::findOne($order->user_id);
        if (!$user)
            return;
        /* @var FormId $form_id */
        $form_id = FormId::find()->where(['order_no' => $order->order_no])->orderBy('addtime DESC')->one();
        $wechat = $this->getWechat();
        if (!$wechat)
            return;
        if (!$form_id)
            return;
        $store = Store::findOne($this->store_id);
        if (!$store || !$store->order_send_tpl)
            return;

        $goods_list = OrderDetail::find()
            ->select('g.name,od.num')
            ->alias('od')->leftJoin(['g' => Goods::tableName()], 'od.goods_id=g.id')
            ->where(['od.order_id' => $order->id, 'od.is_delete' => 0])->asArray()->all();

        $msg_title = '';
        foreach ($goods_list as $goods) {
            $msg_title .= $goods['name'];
        }


        $access_token = $this->wechat->getAccessToken();
        $api = "https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token={$access_token}";
        $data = (object)[
            'touser' => $user->wechat_open_id,
            'template_id' => $store->order_send_tpl,
            'form_id' => $form_id->form_id,
            'page' => 'pages/order/order?status=2',
            'data' => (object)[
                'keyword1' => (object)[
                    'value' => $msg_title,
                    'color' => '#333333',
                ],
                'keyword2' => (object)[
                    'value' => $order->express,
                    'color' => '#333333',
                ],
                'keyword3' => (object)[
                    'value' => $order->express_no,
                    'color' => '#333333',
                ],
            ],
        ];
        $data = \Yii::$app->serializer->encode($data);
        $wechat->curl->post($api, $data);
        $res = json_decode($wechat->curl->response, true);
        if (!empty($res['errcode']) && $res['errcode'] != 0) {
            \Yii::warning("模板消息发送失败：\r\ndata=>{$data}\r\nresponse=>" . \Yii::$app->serializer->encode($res));
        }
    }
}