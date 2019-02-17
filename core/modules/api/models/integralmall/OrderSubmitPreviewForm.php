<?php
/**
 * Created by PhpStorm.
 * User: zc
 * Date: 2018/5/23
 * Time: 9:29
 */

namespace app\modules\api\models\integralmall;

use app\modules\api\models\Model;
use app\models\Store;
use app\models\Address;
use app\models\IntegralGoods;
use app\models\PostageRules;
use app\models\Shop;
use app\models\IntegralOrder;
use app\models\User;
use app\models\IntegralOrderDetail;
use app\models\FormId;
use yii\data\Pagination;
use app\models\Mch;
use app\extensions\Sms;
use app\extensions\SendMail;
use app\models\Register;

class OrderSubmitPreviewForm extends Model
{
    public $store_id;
    public $user_id;
    public $goods_info;
    public $longitude;
    public $latitude;
    public $address_id;
    public $offline;
    public $content;
    public $address_name;
    public $address_mobile;
    public $shop_id;
    public $express_price;
    public $version;
    public $type;
    public $attr;
    public $order_id;
    public $user;
    public $integral;
    public $wechat;
    public $order;
    public $status;
    public $limit;
    public $page;

    public function rules()
    {
        return [
            [['goods_info', 'content', 'address_name', 'address_mobile', 'attr'], 'string'],
            [['address_id', 'offline', 'shop_id', 'type', 'user_id', 'status', 'order_id'], 'integer'],
            [['express_price'], 'number'],
        ];
    }

    public function search()
    {
        $store = Store::findOne($this->store_id);
        if (!$this->validate())
            return $this->errorResponse;
        $goods_info = \Yii::$app->serializer->decode($this->goods_info);
        $goods = IntegralGoods::find()->where(['id' => $goods_info->goods_id, 'is_delete' => 0, 'store_id' => $this->store_id, 'status' => 1,])->asArray()->one();
        $goods_item = IntegralGoods::findOne($goods['id']);
        if (!$goods) {
            return [
                'code' => 1,
                'msg' => '商品不存在或已下架',
            ];
        }
        $goods['attr'] = $goods_info->attr;
        $goods['num'] = 1;

        $address = Address::find()->select('id,name,mobile,province_id,province,city_id,city,district_id,district,detail,is_default')->where([
            'id' => $this->address_id,
            'store_id' => $this->store_id,
            'user_id' => $this->user_id,
            'is_delete' => 0,
        ])->asArray()->one();
        if (!$address) {
            $address = Address::find()->select('id,name,mobile,province_id,province,city_id,city,district_id,district,detail,is_default')->where([
                'store_id' => $this->store_id,
                'user_id' => $this->user_id,
                'is_delete' => 0,
            ])->orderBy('is_default DESC,addtime DESC')->asArray()->one();
        }
        if ($store->send_type != 1) {
            $shop_list = $this->getShopList();
            $is_shop = Shop::find()->where(['is_default' => 1, 'store_id' => $this->store_id])->asArray()->one();
        } else {
            $shop_list = [];
            $is_shop = '';
        }
        if ($address) {
            $express_price = PostageRules::getExpressPrice($this->store_id, $address['city_id'], $goods_item, 1, $address['province_id']);
        }
        return [
            'code' => 0,
            'data' => [
                'goods' => $goods,
                'address' => $address,
                'shop_list' => $shop_list,
                'is_shop' => $is_shop,
                'express_price' => $express_price,
                'send_type' => $store->send_type,
            ],
        ];
    }

    private function getShopList()
    {
        $list = Shop::find()->select(['address', 'mobile', 'id', 'name', 'longitude', 'latitude'])
            ->where(['store_id' => $this->store_id, 'is_delete' => 0])->asArray()->all();
        $distance = array();
        foreach ($list as $index => $item) {
            $list[$index]['distance'] = -1;
            if ($item['longitude'] && $this->longitude) {
                $from = [$this->longitude, $this->latitude];
                $to = [$item['longitude'], $item['latitude']];
                $list[$index]['distance'] = $this->get_distance($from, $to, false, 2);
            }
            $distance[] = $list[$index]['distance'];
        }
        array_multisort($distance, SORT_ASC, $list);
        $min = min(count($list), 30);
        $list_arr = array();
        foreach ($list as $index => $item) {
            if ($index <= $min) {
                $list[$index]['distance'] = $this->distance($item['distance']);
                array_push($list_arr, $list[$index]);
            }
        }
        return $list;
    }

    function get_distance($from, $to, $km = true, $decimal = 2)
    {
        sort($from);
        sort($to);
        $EARTH_RADIUS = 6370.996; // 地球半径系数

        $distance = $EARTH_RADIUS * 2 * asin(sqrt(pow(sin(($from[0] * pi() / 180 - $to[0] * pi() / 180) / 2), 2) + cos($from[0] * pi() / 180) * cos($to[0] * pi() / 180) * pow(sin(($from[1] * pi() / 180 - $to[1] * pi() / 180) / 2), 2))) * 1000;

        if ($km) {
            $distance = $distance / 1000;
        }

        return round($distance, $decimal);
    }

    private static function distance($distance)
    {
        if ($distance == -1) {
            return -1;
        }
        if ($distance > 1000) {
            $distance = round($distance / 1000, 2) . 'km';
        } else {
            $distance .= 'm';
        }
        return $distance;
    }

    public function save()
    {
        if (!$this->validate())
            return $this->errorResponse;
        if ($this->offline == 2) {
            if (!preg_match_all("/1\d{10}/", $this->address_mobile, $arr)) {
                return [
                    'code' => 1,
                    'msg' => '手机号格式错误'
                ];
            }
            $shop = Shop::findOne(['id' => $this->shop_id, 'store_id' => $this->store_id, 'is_delete' => 0]);
            if (!$shop) {
                return [
                    'code' => 1,
                    'msg' => '门店已关闭或不存在',
                ];
            }
        } else {
            $address = Address::findOne([
                'id' => $this->address_id,
                'store_id' => $this->store_id,
                'user_id' => $this->user->id,
            ]);
            if (!$address) {
                return [
                    'code' => 1,
                    'msg' => '收货地址不存在',
                ];
            }
        }
        $goods_info = json_decode($this->goods_info);
        $goods = IntegralGoods::findOne(['id' => $goods_info->goods_id, 'is_delete' => 0, 'store_id' => $this->store_id, 'status' => 1]);
        $user = User::findOne(['id' => $this->user->id, 'store_id' => $this->store_id]);
        if (!$user) {
            return [
                'code' => 1,
                'msg' => '网络异常'
            ];
        }
        if (!$goods) {
            return [
                'code' => 1,
                'msg' => '商品不存在或已下架',
            ];
        }
        $count = IntegralOrder::find()->where(['store_id' => $this->store_id, 'user_id' => $user->id, 'goods_id' => $goods->id, 'is_pay' => 1])->count();
        if($count >= $goods->user_num){
            return [
                'code'=>1,
                'msg'=>'超出购买上限'
            ];
        }
        $attr = json_decode($goods->attr);
        $goods_num = '';
        $goods_pic = '';
        foreach ($attr as $k => &$v) {
            if (json_encode($v->attr_list) == json_encode($goods_info->attr)) {
                $goods_num = $v->num;
                $v->num -= 1;
                if (empty($v->pic)) {
                    $goods_pic = $goods->cover_pic;
                } else {
                    $goods_pic = $v->pic;
                }
            }
        }
        $goods->attr = json_encode($attr);
        $goods->goods_num -= 1;
        if ($goods_num <= 0) {
            return [
                'code' => 1,
                'msg' => '所选商品库存不足',
            ];
        }
        $order = new IntegralOrder();
        $order->store_id = $this->store_id;
        $order->goods_id = $goods->id;
        $order->user_id = $this->user->id;
        $order->order_no = $this->getOrderNo();
        if ($this->offline == 2) {
            $order->name = $this->address_name;
            $order->mobile = $this->address_mobile;
            $order->shop_id = $shop->id;
        } else {
            $order->name = $address->name;
            $order->mobile = $address->mobile;
            $order->address = $address->province . $address->city . $address->district . $address->detail;
            $order->address_data = json_encode([
                'province' => $address->province,
                'city' => $address->city,
                'district' => $address->district,
                'detail' => $address->detail,
            ], JSON_UNESCAPED_UNICODE);
        }
        $order->remark = $this->content ? $this->content : '';
        $order->addtime = time();
        $order->is_offline = $this->offline;
        $order->integral = $goods_info->attr_integral;
        $order->version = $this->version;
        if ($this->offline == 2) {
            $total_price = $goods_info->attr_price;
        } else {
            $total_price = $goods_info->attr_price + $this->express_price;
        }
        if ($this->type == 1) {
            $order->is_pay = 1;
            $order->pay_type = 1;
            $order->pay_time = time();
        } else {
            $order->is_pay = 0;
            $order->pay_type = 0;
            $order->total_price = $total_price;
            $order->pay_price = $total_price;
            $order->express_price = $this->express_price ? $this->express_price : '0';
        }
        if ($order->save()) {
            //减少库存
            $goods->save();
            $order_detail = new IntegralOrderDetail();
            $order_detail->order_id = $order->id;
            $order_detail->goods_id = $goods->id;
            $order_detail->num = 1;
            $order_detail->total_price = $total_price;
            $order_detail->addtime = time();
            $order_detail->is_delete = 0;
            $order_detail->attr = $this->attr;
            $order_detail->pic = $goods_pic;
            $order_detail->pay_integral = $goods_info->attr_integral;
            $order_detail->user_id = $this->user->id;
            $order_detail->store_id = $this->store_id;
            $order_detail->goods_name = $goods->name;
            if ($order_detail->save()) {
                if ($this->type == 1) {
                    $user->integral -= $goods_info->attr_integral;
                    $register = new Register();
                    $register->store_id = $this->store_id;
                    $register->user_id = $user->id;
                    $register->register_time = '..';
                    $register->addtime = time();
                    $register->continuation = 0;
                    $register->type = 11;
                    $register->integral = '-' . $goods_info->attr_integral;
                    $register->order_id = $order->id;
                    $register->save();
                    if ($user->save()) {
                        return [
                            'code' => 0,
                            'type' => 1,
                            'msg' => '兑换成功'
                        ];
                    } else {
                        return [
                            'code' => 1,
                            'msg' => $user->getErrors()
                        ];
                    }
                } else {
                    $this->wechat = $this->getWechat();
                    $this->order = $order;
                    $body = "充值";
                    $res = $this->unifiedOrder($body);
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
                        'data' => (object)$pay_data,
                        'res' => $res,
                        'type' => 2,
                    ];
                }
            }
        } else {
            return $this->getErrorResponse($order);
        }
    }

    public function getOrderNo()
    {
        $store_id = empty($this->store_id) ? 0 : $this->store_id;
        $order_no = null;
        while (true) {
            $order_no = 'G' . date('YmdHis') . rand(100000, 999999);
            $exist_order_no = IntegralOrder::find()->where(['order_no' => $order_no])->exists();
            if (!$exist_order_no)
                break;
        }
        return $order_no;
    }

    private function unifiedOrder($body)
    {
        $res = $this->wechat->pay->unifiedOrder([
            'body' => $body,
            'out_trade_no' => $this->order->order_no,
            'total_fee' => $this->order->pay_price * 100,
            'notify_url' => \Yii::$app->request->hostInfo . \Yii::$app->request->baseUrl . '/in-pay-notify.php',
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
                return $this->unifiedOrder($body);
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

    public function orderList()
    {
        if (!$this->validate())
            return $this->errorResponse;

        $query = IntegralOrder::find()
            ->where([
                'store_id' => $this->store_id,
                'is_delete' => 0,
                'user_id' => $this->user_id
            ])->with(['user' => function ($query) {
                $query->where([
                    'store_id' => $this->store_id,
                    'is_delete' => 0,
                ]);
            }])->with(['detail' => function ($query) {
                $query->where([
                    'is_delete' => 0,
                ]);
            }]);

        if ($this->status == 0) {//待付款
            $query->andWhere([
                'is_pay' => 0,
            ]);
            $list = $query
                ->orderBy('addtime DESC')
                ->asArray()
                ->all();
        }
        if ($this->status == 1) {//待发货
            $query->andWhere([
                'is_send' => 0,
            ])->andWhere(['or', ['is_pay' => 1], ['pay_type' => 1]]);
            $list = $query
                ->orderBy('pay_time DESC')
                ->asArray()
                ->all();
        }
        if ($this->status == 2) {//待收货
            $query->andWhere([
                'is_send' => 1,
                'is_confirm' => 0,
            ]);
            $list = $query
                ->orderBy('send_time DESC')
                ->asArray()
                ->all();
        }
        if ($this->status == 3) {//已完成
            $query->andWhere([
                'is_confirm' => 1,
            ]);
            $list = $query
                ->orderBy('confirm_time DESC')
                ->asArray()
                ->all();
        }
        foreach ($list as $key => &$value) {
            if ($value['mch_id'] == 0) {
                $value['mch'] = [
                    'id' => 0,
                    'name' => '平台自营',
                    'logo' => '',
                ];
            } else {
                $mch = Mch::findOne($value['mch_id']);
                $value['mch'] = [
                    'id' => $mch->id,
                    'name' => $mch->name,
                    'logo' => $mch->logo,
                ];
            }
            $value['addtime'] = date('Y-m-d H:i:s', $value['addtime']);
            $value['detail']['attr'] = json_decode($value['detail']['attr']);
        }
        return [
            'code' => 0,
            'data' => [
                'list' => $list
            ]
        ];
    }

    public function revoke()
    {
        $order = IntegralOrder::findOne([
            'store_id' => $this->store_id,
            'user_id' => $this->user_id,
            'id' => $this->order_id,
            'is_send' => 0,
            'is_delete' => 0,
            'is_cancel' => 0
        ]);
        if (!$order) {
            return [
                'code' => 1,
                'msg' => '订单不存在'
            ];
        }
        if ($order->is_pay == 1) {
            $order->apply_delete = 1;
            Sms::send_refund($order->store_id, $order->order_no);
            if ($order->save()) {
                return [
                    'code' => 0,
                    'msg' => '订单取消申请已提交，请等候管理员审核'
                ];
            } else {
                return $this->getErrorResponse($order);
            }
        }
        $order_detail = IntegralOrderDetail::find()->where(['order_id' => $order->id, 'is_delete' => 0])->one();
        $order_detail_attr = json_decode($order_detail->attr);
        foreach ($order_detail_attr as $k => &$v) {
            unset($v->attr_group_id);
            unset($v->attr_group_name);
        }
        $goods = IntegralGoods::findOne($order_detail->goods_id);
        $attr = json_decode($goods->attr);
        foreach ($attr as $key => $item) {
            if (json_encode($item->attr_list) == json_encode($order_detail_attr)) {
                $item->num += 1;
            }
        }
        $goods->attr = json_encode($attr);
        $goods->goods_num += 1;
        $order->is_delete = 1;
        $order_detail->is_delete = 1;
        //        商品库存恢复
        $goods->save();
        //     删除订单
        $order->save();
        //删除订单详情
        $order_detail->save();
    }


    public function orderPay()
    {
        $order = IntegralOrder::findOne(['id' => $this->order_id, 'user_id' => $this->user->id, 'store_id' => $this->store_id, 'is_pay' => 0]);
        if (!$order) {
            return [
                'code' => 1,
                'msg' => '成功'
            ];
        }
        $count = IntegralOrder::find()->where(['store_id' => $this->store_id, 'user_id' => $this->user->id, 'goods_id' => $order->goods_id, 'is_pay' => 1])->count();
        $goods = IntegralGoods::findOne(['store_id' => $this->store_id, 'id' => $order->goods_id]);
        if(!$goods){
            return [
                'code'=>1,
                'msg'=>'商品不存在'
            ];
        }
        if ($count >= $goods->user_num) {
            return [
                'code' => 1,
                'msg' => '超出购买上限'
            ];
        }
        $this->wechat = $this->getWechat();
        $this->order = $order;
        $body = "充值";
        $res = $this->unifiedOrder($body);
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
            'data' => (object)$pay_data,
            'res' => $res,
            'type' => 3,
        ];
    }

    public function confirm()
    {
        if (!$this->validate()) {
            return $this->errorResponse;
        }
        $order = IntegralOrder::findOne([
            'store_id' => $this->store_id,
            'user_id' => $this->user_id,
            'id' => $this->order_id,
            'is_send' => 1,
            'is_delete' => 0,
        ]);
        if (!$order) {
            return [
                'code' => 1,
                'msg' => '订单不存在'
            ];
        }
        $order->is_confirm = 1;
        $order->confirm_time = time();
        if ($order->save()) {
            return [
                'code' => 0,
                'msg' => '已确认收货'
            ];
        } else {
            return [
                'code' => 1,
                'msg' => '确认收货失败'
            ];
        }
    }
}