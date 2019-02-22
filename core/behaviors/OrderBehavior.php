<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/14
 * Time: 17:46
 */

namespace app\behaviors;

use app\extensions\PinterOrder;
use app\models\Goods;
use app\models\Level;
use app\models\Mch;
use app\models\MchAccountLog;
use app\models\MiaoshaGoods;
use app\models\MsGoods;
use app\models\MsOrder;
use app\models\MsOrderRefund;
use app\models\Online;
use app\models\Order;
use app\models\OrderDetail;
use app\models\OrderRefund;
use app\models\OrderShare;
use app\models\PtOrder;
use app\models\PtOrderRefund;
use app\models\Register;
use app\models\Setting;
use app\models\Store;
use app\models\User;
use app\models\UserShareMoney;
use yii\db\Query;
use yii\web\Controller;

/**
 * 检查订单过期未付款、超时自动确认收货、分销佣金发放等
 * @property \app\models\Store $store;
 * @property \app\models\Setting $share_setting;
 *
 */
class OrderBehavior extends BaseBehavior
{
    protected $only_routes = [
        /*'mch/photographer/*',
        'mch/share/*',
        'mch/miaosha/*',
        'api/order/*',*/
        'api/requirement/*',
        'api/photographer/*',
        'api/user/*',
    ];

    public $store_id;
    public $store;
    public $share_setting;

    public function beforeAction($e)
    {
        \Yii::warning('----ORDER BEHAVIOR----');
        $order_behavior_running = 'order_behavior_running';
        if (\Yii::$app->cache->get($order_behavior_running)) {
            return true;
        }
        \Yii::$app->cache->set($order_behavior_running, true, 60);
        $this->store_id = isset(\Yii::$app->controller->store) ? \Yii::$app->controller->store->id : 0;
        if (!$this->store_id) {
            \Yii::$app->cache->delete($order_behavior_running);
            return true;
        }
        $this->store = Store::findOne($this->store_id);
        $this->yesterday();
        $this->yesterdayOnlinePay();
    }

    private function yesterday()
    {
        $yesterday = strtotime(date("Y-m-d 00:00:00", strtotime("-1 day")));
        $today = strtotime(date('Y-m-d 00:00:00'));
        $query = Online::find()->where(['store_id' => $this->store_id, 'total' => 0, 'is_pay' => 0]);
        if (is_int($yesterday)) {
            $query->andWhere(['>', 'addtime', $yesterday]);
        }
        if (is_int($today)) {
            $query->andWhere(['<', 'addtime', $today]);
        }
        $online_list = $query->limit(20)->asArray()->all();

        $yes_end = $yesterday + 23 * 60 * 60 + 59 * 60+59;
        foreach ($online_list as $item) {

            $online = Online::findOne($item['id']);
            if(!$online){
                return;
            }
            if($item['end']==0){
                $yes_end=$yes_end;
            }else{
                $yes_end=$item['end'];
            }
            $sub =($yes_end - $online->start)/(60 * 60);
            $sub= sprintf("%.2f",$sub);
            if($sub>=5){
                 $sub=5;
            }
            if ($sub==0){
                $online->end =$yes_end;
            }else{
                $online->end = $online->start + $sub * 60 * 60;
            }
            $online->total = $sub;
            $online->save();
        }
    }


    private function yesterdayOnlinePay()
    {
        $yesterday = strtotime(date("Y-m-d 00:00:00", strtotime("-1 day")));
        $today = strtotime(date('Y-m-d 00:00:00'));
        $query = Online::find()->where(['store_id' => $this->store_id, 'is_pay' => 0])
            ->andWhere(['>', 'total', 0]);



        if (is_int($yesterday)) {
            $query->andWhere(['>', 'addtime', $yesterday]);
        }
        if (is_int($today)) {
            $query->andWhere(['<', 'addtime', $today]);
        }
        $online_list = $query->limit(20)->asArray()->all();
        foreach ($online_list as $item) {
            $online = Online::findOne($item['id']);

            Online::updateAll(['is_pay'=>1],['id'=>$item['id']]);


             $user=User::findOne($online->user_id);
             if($user){
                 $user->money+=$online->total;
                 $user->save();
             }
            $online->is_pay;
            $online->save();
        }
    }


    /**
     * @param $parent_id
     * @param $money
     * @return array
     *
     */
    private function money($parent_id, $money)
    {
        if ($parent_id == 0) {
            return ['code' => 1, 'parent_id' => 0];
        }
        $parent = User::findOne(['id' => $parent_id]);
        if (!$parent) {
            return ['code' => 1, 'parent_id' => 0];
        }
        $parent->total_price += $money;
        $parent->price += $money;
        if ($parent->save()) {
            return [
                'code' => 0,
                'parent_id' => $parent->parent_id,
            ];
        } else {
            return [
                'code' => 1,
                'parent_id' => 0,
            ];
        }
    }

    /**
     * @param $parent_id
     * @param $percent
     * @param $price
     * @return array
     * 已废弃
     */
    public static function shareMoney($parent_id, $percent, $price)
    {
        if ($parent_id == 0) {
            return ['code' => 1, 'parent_id' => 0];
        }
        $parent = User::findOne(['id' => $parent_id]);
        if (!$parent) {
            return ['code' => 1, 'parent_id' => 0];
        }
        $parent->total_price += ($price * $percent / 100);
        $parent->price += ($price * $percent / 100);

        if ($parent->save()) {
            return [
                'code' => 0,
                'parent_id' => $parent->parent_id,
            ];
        } else {
            return [
                'code' => 1,
                'parent_id' => 0,
            ];
        }
    }

    /**
     * @param $id
     * 佣金发放
     */
    private function share_money($id)
    {
        $order = Order::findOne($id);
        if ($this->share_setting->level == 0) {
            return;
        }
        if ($order->is_price != 0) {
            return;
        }
        //分销商自购返利
        if ($order->rebate > 0) {
            $user = User::findOne(['id' => $order->user_id]);
            $user->total_price += doubleval($order->rebate);
            $user->price += doubleval($order->rebate);
            $user->save();
            $order->is_price = 1;
            UserShareMoney::set($order->rebate, $user->id, $order->id, 0, 4, $order->store_id, 0);
        }
        //仅自购
        if ($this->share_setting->level == 4) {
            return;
        }
        //一级佣金发放
        if ($this->share_setting->level >= 1) {
            $user_1 = User::findOne($order->parent_id);
            if (!$user_1) {
                $order->save();
                return;
            }
            $user_1->total_price += $order->first_price;
            $user_1->price += $order->first_price;
            $user_1->save();
            UserShareMoney::set($order->first_price, $user_1->id, $order->id, 0, 1, $this->store_id, 0);
            $order->is_price = 1;
            $order->save();
        }
        //二级佣金发放
        if ($this->share_setting->level >= 2) {
            $user_2 = User::findOne($order->parent_id_1);
            if (!$user_2) {
                if ($user_1->parent_id != 0 && $order->parent_id_1 == 0) {
                    $res = self::money($user_1->parent_id, $order->second_price);
                    UserShareMoney::set($order->second_price, $user_1->parent_id, $order->id, 0, 2, $this->store_id, 0);
                    if ($res['parent_id'] != 0 && $this->share_setting->level == 3) {
                        $res = self::money($res['parent_id'], $order->third_price);
                        UserShareMoney::set($order->third_price, $res['parent_id'], $order->id, 0, 3, $this->store_id, 0);
                    }
                }
                return;
            }
            $user_2->total_price += $order->second_price;
            $user_2->price += $order->second_price;
            $user_2->save();
            UserShareMoney::set($order->second_price, $user_2->id, $order->id, 0, 2, $this->store_id, 0);
        }
        //三级佣金发放
        if ($this->share_setting->level >= 3) {
            $user_3 = User::findOne($order->parent_id_2);
            if (!$user_3) {
                if ($user_2->parent_id != 0 && $order->parent_id_2 == 0) {
                    self::money($user_2->parent_id, $order->third_price);
                    UserShareMoney::set($order->third_price, $user_2->parent_id, $order->id, 0, 3, $this->store_id, 0);
                }
                return;
            }
            $user_3->total_price += $order->third_price;
            $user_3->price += $order->third_price;
            $user_3->save();
            UserShareMoney::set($order->third_price, $user_3->id, $order->id, 0, 3, $this->store_id, 0);
        }
    }

    /**
     * 佣金发放 秒杀
     */
    public function share_money_ms($id)
    {
        $order = MsOrder::findOne($id);
        if ($this->share_setting->level == 0) {
            return false;
        }
        if ($order->is_price != 0) {
            return false;
        }
        if ($order->rebate > 0) {
            $user = User::findOne(['id' => $order->user_id]);
            $user->total_price += doubleval($order->rebate);
            $user->price += doubleval($order->rebate);
            $user->save();
            $order->is_price = 1;
            UserShareMoney::set($order->rebate, $user->id, $order->id, 0, 4, $order->store_id, 1);
        }
        //仅自购
        if ($this->share_setting->level == 4) {
            return false;
        }
        //一级佣金发放
        if ($this->share_setting->level >= 1) {
            $user_1 = User::findOne($order->parent_id);
            if (!$user_1) {
                $order->save();
                return false;
            }
            $user_1->total_price += doubleval($order->first_price);
            $user_1->price += doubleval($order->first_price);
            $user_1->save();
            UserShareMoney::set($order->first_price, $user_1->id, $order->id, 0, 1, $this->store_id, 1);
            $order->is_price = 1;
            $order->save();
        }
        //二级佣金发放
        if ($this->share_setting->level >= 2) {
            $user_2 = User::findOne($order->parent_id_1);
            if (!$user_2) {
                return false;
            }
            $user_2->total_price += doubleval($order->second_price);
            $user_2->price += doubleval($order->second_price);
            $user_2->save();
            UserShareMoney::set($order->second_price, $user_2->id, $order->id, 0, 2, $this->store_id, 1);
        }
        //三级佣金发放
        if ($this->share_setting->level >= 3) {
            $user_3 = User::findOne($order->parent_id_2);
            if (!$user_3) {
                return false;
            }
            $user_3->total_price += doubleval($order->third_price);
            $user_3->price += doubleval($order->third_price);
            $user_3->save();
            UserShareMoney::set($order->third_price, $user_3->id, $order->id, 0, 3, $this->store_id, 1);
        }
        return true;
    }

    /**
     * @param $order_id
     * @param int $type
     * @return bool
     * 佣金发放  拼团
     */
    public function share_money_1($order_id, $type = 0)
    {
        if ($this->share_setting->level == 0) {
            return false;
        }
        $order_share = OrderShare::findOne(['store_id' => $this->store_id, 'type' => $type, 'order_id' => $order_id]);
        if ($order_share->rebate > 0) {
            $user = User::findOne(['id' => $order_share->user_id]);
            $user->total_price += doubleval($order_share->rebate);
            $user->price += doubleval($order_share->rebate);
            $user->save();
            UserShareMoney::set($order_share->rebate, $user->id, $order_share->order_id, 0, 4, $this->store_id, 2);
        }
        //仅自购
        if ($this->share_setting->level == 4) {
            return false;
        }
        //一级佣金发放
        if ($this->share_setting->level >= 1) {
            $user_1 = User::findOne($order_share->parent_id_1);
            if (!$user_1) {
                return false;
            }
            $user_1->total_price += doubleval($order_share->first_price);
            $user_1->price += doubleval($order_share->first_price);
            $user_1->save();
            UserShareMoney::set($order_share->first_price, $user_1->id, $order_id, 0, 1, $this->store_id, 2);
            $order_share->save();
        }
        //二级佣金发放
        if ($this->share_setting->level >= 2) {
            $user_2 = User::findOne($order_share->parent_id_2);
            if (!$user_2) {
                return false;
            }
            $user_2->total_price += doubleval($order_share->second_price);
            $user_2->price += doubleval($order_share->second_price);
            $user_2->save();
            UserShareMoney::set($order_share->second_price, $user_2->id, $order_id, 0, 2, $this->store_id, 2);
        }
        //三级佣金发放
        if ($this->share_setting->level >= 3) {
            $user_3 = User::findOne($order_share->parent_id_3);
            if (!$user_3) {
                return false;
            }
            $user_3->total_price += doubleval($order_share->third_price);
            $user_3->price += doubleval($order_share->third_price);
            $user_3->save();
            UserShareMoney::set($order_share->third_price, $user_3->id, $order_id, 0, 3, $this->store_id, 2);
        }
        return true;
    }

    /**
     * 积分发放 --商城
     */
    private function give_integral($id)
    {
        $give = Order::findOne($id);
        if ($give['give_integral'] != 0) {
            return;
        }
        $integral = OrderDetail::find()
            ->andWhere(['order_id' => $give['id'], 'is_delete' => 0])
            ->select([
                'sum(integral)',
            ])->scalar();

        $giveUser = User::findOne(['id' => $give['user_id']]);
        $giveUser->integral += $integral;
        $giveUser->total_integral += $integral;
        $giveUser->save();
        $give->give_integral = 1;
        $give->save();
        $register = new Register();
        $register->store_id = $this->store->id;
        $register->user_id = $giveUser->id;
        $register->register_time = '..';
        $register->addtime = time();
        $register->continuation = 0;
        $register->type = 7;
        $register->integral = $integral;
        $register->order_id = $give->id;
        $register->save();
    }

    /**
     * 积分发放 --秒杀
     */
    private function give_integral_ms($id)
    {
        $give = MsOrder::findOne($id);
        if ($give['give_integral'] != 0) {
            return;
        }
        $integral = $give['integral_amount'];

        $giveUser = User::findOne(['id' => $give['user_id']]);
        $giveUser->integral += $integral;
        $giveUser->total_integral += $integral;
        $giveUser->save();
        $give->give_integral = 1;
        $give->save();
        $register = new Register();
        $register->store_id = $this->store->id;
        $register->user_id = $giveUser->id;
        $register->register_time = '..';
        $register->addtime = time();
        $register->continuation = 0;
        $register->type = 8;
        $register->integral = $integral;
        $register->order_id = $give->id;
        $register->save();
    }

    /**
     * 处理未在规定时间内付款的秒杀订单
     * @param ActionEvent $event
     * @return bool
     */
    private function checkMsNoPayOrderTimeout($event)
    {
        $order_max = 100; //每次最多处理的个数，防止运行太久
        $cache_key = 'pt_no_pay_order_timeout_checker';
        if (\Yii::$app->cache->get($cache_key)) {
            return true;
        }

        \Yii::$app->cache->set($cache_key, true, 10);
        /** @var Store $store */
        $store = isset($event->action->controller->store) ? $event->action->controller->store : null;
        if (!$store) {
            \Yii::$app->cache->set($cache_key, false);
            return true;
        }

        /** @var MsOrder[] $order_list */
        $order_list = MsOrder::find()->where([
            'AND',
            [
                'is_pay' => 0,
                'is_cancel' => 0,
                'store_id' => $store->id,
                'is_delete' => 0,
            ],
            ['<=', 'limit_time', time()],
            ['!=', 'pay_type', 2],
        ])->limit($order_max)->all();
        foreach ($order_list as $order) {
            $order->is_cancel = 1;
            $order->save(false);
            if ($order->save(false)) {
                //秒杀订单所属秒杀时间段库存恢复
                $miaosha_goods = MiaoshaGoods::findOne([
                    'goods_id' => $order->goods_id,
                    'start_time' => intval(date('H', $order->addtime)),
                    'open_date' => date('Y-m-d', $order->addtime),
                ]);
                $attr_id_list = [];
                foreach (json_decode($order->attr) as $item) {
                    array_push($attr_id_list, $item->attr_id);
                }

                $miaosha_goods->numAdd($attr_id_list, $order->num);
                //秒杀商品总库存恢复
                $goods = MsGoods::findOne($order->goods_id);
                $attr_id_list = [];
                foreach (json_decode($order->attr) as $item) {
                    array_push($attr_id_list, $item->attr_id);
                }

                $goods->numAdd($attr_id_list, $order->num);

                $integral = json_decode($order->integral)->forehead_integral;
                if ($integral) {
                    $user = User::findOne(['id' => $order->user_id]);
                    $user->integral += $integral ? $integral : 0;
                    $user->save();
                    $register = new Register();
                    $register->store_id = $this->store->id;
                    $register->user_id = $user->id;
                    $register->register_time = '..';
                    $register->addtime = time();
                    $register->continuation = 0;
                    $register->type = 13;
                    $register->integral = $integral;
                    $register->order_id = $order->id;
                    $register->save();
                }
            }
        }

        \Yii::$app->cache->set($cache_key, false);
        return true;
    }

    /**
     * 入驻商户订单金额转到商户余额
     * @param \yii\base\ActionEvent $e
     */
    private function transferToMch($e)
    {
        try {
            if (!isset($e->action->controller->store)) {
                return;
            }
            \Yii::warning('---lu---');
            /** @var Order[] $order_list */
            $order_list = Order::find()->where([
                'AND',
                ['is_pay' => 1],
                ['pay_type' => [1, 3]],
                ['is_sale' => 1],
                ['is_transfer' => 0],
                ['!=', 'mch_id', 0],
            ])->limit(50)
                ->all();
            foreach ($order_list as $order) {
                $mch = Mch::findOne($order->mch_id);
                if (!$mch) {
                    continue;
                }

                $mch->account_money = floatval($mch->account_money) + floatval($order->pay_price * (1 - floatval($mch->transfer_rate) / 1000));
                $mch->save();
                $order->is_transfer = 1;
                if (!$order->save()) {
                    \Yii::warning($order->errors);
                }

                $log = new MchAccountLog();
                $log->store_id = $order->store_id;
                $log->mch_id = $mch->id;
                $log->type = 1;
                $log->price = floatval($order->pay_price * (1 - floatval($mch->transfer_rate) / 1000));
                $log->desc = '订单（' . $order->order_no . '）结算';
                $log->addtime = time();
                if (!$log->save()) {
                    \Yii::warning($log->errors);
                }

            }
        } catch (\Exception $e) {
        }
    }

    //    private function setMiaoshaSellNum($miaosha_goods_id, $attr_id_list, $num)
    //    {
    //        $miaosha_goods = MiaoshaGoods::findOne($miaosha_goods_id);
    //        if (!$miaosha_goods)
    //            return false;
    //        sort($attr_id_list);
    //        $attr_data = json_decode($miaosha_goods->attr, true);
    //        foreach ($attr_data as $i => $attr_row) {
    //            $_tmp_attr_id_list = [];
    //            foreach ($attr_row['attr_list'] as $attr) {
    //                $_tmp_attr_id_list[] = intval($attr['attr_id']);
    //            }
    //            sort($_tmp_attr_id_list);
    //            if ($_tmp_attr_id_list == $attr_id_list) {
    //                $attr_data[$i]['sell_num'] = intval($attr_data[$i]['sell_num']) + intval($num);
    //                break;
    //            }
    //        }
    //        $miaosha_goods->attr = json_encode($attr_data, JSON_UNESCAPED_UNICODE);
    //        $res = $miaosha_goods->save();
    //        return $res;
    //    }

}
