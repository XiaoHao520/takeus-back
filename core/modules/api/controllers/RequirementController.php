<?php
/**
 * Created by IntelliJ IDEA.
 * User: luwei
 * Date: 2017/7/18
 * Time: 11:28
 */

namespace app\modules\api\controllers;


use app\hejiang\ApiResponse;
use app\hejiang\BaseApiResponse;
use app\models\Address;
use app\models\Coupon;
use app\models\CouponAutoSend;
use app\models\FormId;
use app\models\Level;
use app\models\MoneyRecord;
use app\models\Option;
use app\models\Order;
use app\models\OrderMsg;
use app\models\OrderPic;
use app\models\Photographer;
use app\models\PhotographerLevel;
use app\models\Requirement;
use app\models\RequirementOrder;
use app\models\Reward;
use app\models\Setting;
use app\models\Share;
use app\models\Store;
use app\models\Taocan;
use app\models\User;
use app\models\UserAuthLogin;
use app\models\UserCard;
use app\models\UserCenterForm;
use app\models\UserCenterMenu;
use app\models\UserCoupon;
use app\models\UserFormId;
use app\modules\api\behaviors\LoginBehavior;
use app\modules\api\models\AddressDeleteForm;
use app\modules\api\models\AddressSaveForm;
use app\modules\api\models\AddressSetDefaultForm;
use app\modules\api\models\AddWechatAddressForm;
use app\modules\api\models\AreaReOrderListForm;
use app\modules\api\models\CardListForm;
use app\modules\api\models\FavoriteAddForm;
use app\modules\api\models\FavoriteListForm;
use app\modules\api\models\FavoriteRemoveForm;
use app\modules\api\models\OrderListForm;
use app\modules\api\models\PhotographerListForm;
use app\modules\api\models\ReOrderListForm;
use app\modules\api\models\ReOrderPayForm;
use app\modules\api\models\ReOrderSubmitForm;
use app\modules\api\models\RewardPayForm;
use app\modules\api\models\TopicFavoriteForm;
use app\modules\api\models\TopicFavoriteListForm;
use app\modules\api\models\WechatDistrictForm;
use app\modules\api\models\QrcodeForm;
use app\modules\api\models\OrderMemberForm;
use app\models\SmsSetting;
use app\modules\api\models\UserForm;
use app\extensions\Sms;
use function PHPSTORM_META\type;
use yii\db\Exception;

class RequirementController extends Controller
{


    public function actionPhotographerLevel()
    {
        $level_list = PhotographerLevel::find()->where(['store_id' => $this->store_id, 'is_delete' => 0])->asArray()->all();
        return new BaseApiResponse([
            'code' => 1,
            'data' => $level_list
        ]);

    }

    public function actionRequirementTypes()
    {
        $requirement_types = Requirement::find()->where(['store_id' => $this->store_id, 'is_delete' => 0])->asArray()->all();
        return new BaseApiResponse([
            'code' => 1,
            'data' => $requirement_types
        ]);
    }

    public function actionAreaPhotographer()
    {
        $form = new PhotographerListForm();
        $u_lat = \Yii::$app->request->get('u_lat');
        $u_lon = \Yii::$app->request->get('u_lon');
        $level_id = \Yii::$app->request->get("level_id");
        $type_id = \Yii::$app->request->get("type_id");
        $form_id = \Yii::$app->request->get("form_id");
        $form->store_id = $this->store_id;

        //   $u_lat=\Yii::$app->request->get('u_lat');
        $form->u_lat = $u_lat;
        $form->u_lon = $u_lon;
        $form->level_id = $level_id;
        $form->page = \Yii::$app->request->get('page');
        $taocan_list = Taocan::find()->where(['requirement_id' => $type_id, 'level_id' => $level_id, 'is_delete' => 0, 'store_id' => $this->store_id])->asArray()->all();

        if($form_id){
            $user=User::findOne(\Yii::$app->user->identity->id);
            FormId::addFormId([
                'store_id'=>$this->store_id,
                'user_id'=>$user->id,
                'wechat_open_id'=>$user->wechat_open_id,
                'form_id'=>$form_id,
                'type'=>'form_id',
                'order_no' => 'APL' .time()
            ]);
        }



        if (count($taocan_list)) {
            return new BaseApiResponse($form->search());
        } else {
            return new BaseApiResponse(['code' => 1, 'msg' => '暂无您想要的套餐组合']);
        }


    }

    public function actionGetPrice()
    {

        $level_id = \Yii::$app->request->get('level_id');
        $type_id = \Yii::$app->request->get('type_id');
        $taocan = Taocan::findOne(['store_id' => $this->store_id, 'level_id' => $level_id, 'requirement_id' => $type_id, 'is_delete' => 0]);

        if ($taocan) {
            return new BaseApiResponse(['code' => 0, 'data' => $taocan]);
        } else {
            return new BaseApiResponse(['code' => 1, 'msg' => '找不到相关套餐']);
        }
    }

    public function actionSubmitOrder()
    {
        $model = \Yii::$app->request->get();
        $form = new ReOrderSubmitForm();
        $form->attributes = $model;
        $form->store_id = $this->store_id;
        $form->user_id = \Yii::$app->user->identity->id;


        // Sms::sendNewOrder($this->store_id,60);



        return new BaseApiResponse($form->save());
    }

    public function actionOrderDetail()
    {
        $order_id = \Yii::$app->request->get('order_id');
        $order = RequirementOrder::find()->alias('o')->leftJoin(Photographer::tableName() . 'p', 'p.id=o.photographer_id')
            ->leftJoin(PhotographerLevel::tableName() . 'pl', 'pl.id=p.level_id')
            ->leftJoin(Requirement::tableName() . 'r', 'r.id=o.requirement_id')
            ->where(['o.id' => $order_id])
            ->select('o.*,pl.name as level_name,r.name as r_name,p.name as p_name,p.header_url')->asArray()->one();
        $order['coupon_list'] = $this->getCouponList($order['price']);

        if ($order) {

            return new BaseApiResponse(['code' => 0, 'data' => $order]);

        } else {
            return new BaseApiResponse(['code' => 1, 'msg' => '找不到订单']);
        }


    }

    private function getCouponList($goods_total_price)
    {
        $list = UserCoupon::find()->alias('uc')
            ->leftJoin(['c' => Coupon::tableName()], 'uc.coupon_id=c.id')
            ->leftJoin(['cas' => CouponAutoSend::tableName()], 'uc.coupon_auto_send_id=cas.id')
            ->where([
                'AND',
                ['uc.is_delete' => 0],
                ['uc.is_use' => 0],
                ['uc.is_expire' => 0],
                ['uc.user_id' => \Yii::$app->user->identity->id],
                ['<=', 'c.min_price', $goods_total_price],
            ])
            ->select('uc.id user_coupon_id,c.sub_price,c.min_price,cas.event,uc.begin_time,uc.end_time,uc.type,c.appoint_type,c.cat_id_list,c.goods_id_list')
            ->asArray()->all();
        $events = [
            0 => '平台发放',
            1 => '分享红包',
            2 => '购物返券',
            3 => '领券中心'
        ];
        $new_list = [];
        foreach ($list as $i => $item) {
            if ($item['begin_time'] > (strtotime(date('Y-M-d')) + 86400) || $item['end_time'] < time()) {
                continue;
            }
            $list[$i]['status'] = 0;
            if ($item['is_use'])
                $list[$i]['status'] = 1;
            if ($item['is_expire'])
                $list[$i]['status'] = 2;
            $list[$i]['min_price_desc'] = $item['min_price'] == 0 ? '无门槛' : '满' . $item['min_price'] . '元可用';
            $list[$i]['begin_time'] = date('Y.m.d H:i', $item['begin_time']);
            $list[$i]['end_time'] = date('Y.m.d H:i', $item['end_time']);
            if (!$item['event']) {
                if ($item['type'] == 2) {
                    $list[$i]['event'] = $item['event'] = 3;
                } else {
                    $list[$i]['event'] = $item['event'] = 0;
                }
            }
            $list[$i]['event_desc'] = $events[$item['event']];
            $list[$i]['min_price'] = doubleval($item['min_price']);
            $list[$i]['sub_price'] = doubleval($item['sub_price']);


            $new_list[] = $list[$i];
        }
        return $new_list;
    }

    public function actionAreaOrder()
    {

        $form = new AreaReOrderListForm();
        $u_lat = \Yii::$app->request->get('u_lat');
        $u_lon = \Yii::$app->request->get('u_lon');
        $form->store_id = $this->store_id;

        $form->u_lat = $u_lat;
        $form->u_lon = $u_lon;
        return new BaseApiResponse($form->search());


    }


    public function actionPayData()
    {
        $order_id = \Yii::$app->request->get('order_id');
        $user = \Yii::$app->user->identity;
        $form = new ReOrderPayForm();
        $form->attributes = \Yii::$app->request->get();
        $form->user = $user;
        $form->order_id = $order_id;
        $form->store_id = $this->store_id;
        return new BaseApiResponse($form->search());
    }


    public function actionAddPrice()
    {
        $order_id = \Yii::$app->request->get('order_id');
        $form = new RewardPayForm();
        $form->order_id = $order_id;
        $form->store_id = $this->store_id;
        $form->user = \Yii::$app->user->identity;

        return new BaseApiResponse($form->search());
    }


    public function actionDownload()
    {

        $order_id = \Yii::$app->request->get('order_id');
        $is_compress = \Yii::$app->request->get('type');

        $pic_list = OrderPic::find()->where(['order_id' => $order_id, 'is_delete' => 0, 'is_compress' => $is_compress])->asArray()->all();
        return new BaseApiResponse(['code' => 0, 'data' => $pic_list]);
    }


    //订单列表
    public function actionOrderList()
    {
        $form = new ReOrderListForm();
        $form->attributes = \Yii::$app->request->get();
        $form->store_id = $this->store->id;
        $user_id = \Yii::$app->user->identity->id;
        $is_photographer = \Yii::$app->request->get('is_photographer');
        if ($is_photographer) {
            $photographer = Photographer::findOne(['user_id' => $user_id]);
            if ($photographer) {
                $form->photographer_id = $photographer->id;
            } else {
                return new BaseApiResponse(['code' => 1, 'msg' => '系统错误']);
            }
        }
        $form->user_id = $user_id;







        OrderMsg::updateAll(['is_read' => 1], ['store_id' => $this->store_id, 'user_id' => $user_id, 'is_delete' => 0]);
        return new BaseApiResponse($form->search());
    }

    public function actionConfirm()
    {

        $order_id = \Yii::$app->request->get('order_id');
        $order = RequirementOrder::findOne(['id' => $order_id, 'status' => 3, 'is_pay' => 1, 'is_cancel' => 0, 'is_delete' => 0,'is_confirm'=>0]);
        $t = \Yii::$app->db->beginTransaction();
        $store=$this->store;
        try {

            if ($order) {
                $order->status = 4;
                $order->is_confirm = 1;
                $order->confirm_time = time();
                if ($order->save()) {
                    $money_record = new MoneyRecord();
                    $photographer = Photographer::findOne($order->photographer_id);
                    if ($photographer) {
                        $all_money = 0;
                        $user = User::findOne($photographer->user_id);
                        $all_money += $order->price;
                        $money_record->user_id = $photographer->user_id;
                        $money_record->price = strval($order->price*(1-$store->rate/100));
                        $money_record->type = 1;
                        $money_record->detail = '摄影订单入账';
                        $money_record->status = 1;
                        $money_record->addtime = time();
                        $money_record->store_id = $this->store_id;
                        if ($money_record->save()) {            //增加费用只做了一次
                            $reward = Reward::findOne(['order_id' => $order_id, 'is_pay' => 1, 'status' => 0]);
                            if ($reward) {
                                $reward->status = 1;
                                if ($reward->save()) {
                                    $all_money += $reward->price*(1-$store->rate/100);
                                    $money_record = new MoneyRecord();
                                    $money_record->user_id = $photographer->user_id;
                                    $money_record->price = strval($reward->price*(1-$store->rate/100));
                                    $money_record->type = 2;
                                    $money_record->detail = '增加服务费入账';
                                    $money_record->status = 1;
                                    $money_record->addtime = time();
                                    $money_record->store_id = $this->store_id;
                                    if ($money_record->save()) {
                                        $user->money += ($all_money-$all_money*$store->rate/100);
                                        $user->save();


                                        $t->commit();
                                        return new BaseApiResponse(['code' => 0, 'msg' => '操作成功---']);
                                    } else {
                                        $t->rollBack();
                                        return new BaseApiResponse(['code' => 1, 'msg' => '操作失败']);
                                    }
                                } else {
                                    $t->rollBack();
                                    return new BaseApiResponse(['code' => 1, 'msg' => '操作失败']);
                                }
                            } else {
                                $user->money +=($all_money-$all_money*$store->rate/100);
                                $user->save();

                                $t->commit();
                                return new BaseApiResponse(['code' => 0, 'msg' => '操作成功']);
                            }
                        } else {
                            $t->rollBack();
                            return new BaseApiResponse(['code' => 1, 'msg' => '操作失败']);
                        }
                    } else {
                        $t->rollBack();
                        return new BaseApiResponse(['code' => 1, 'msg' => '找不到摄影师']);
                    }


                    return new BaseApiResponse(['code' => 0, 'msg' => '操作成功']);
                } else {
                    return new BaseApiResponse(['code' => 1, 'msg' => '操作成功', 'data' => $order->errors]);
                }
            } else {
                return new BaseApiResponse(['code' => 1, 'msg' => '找不到订单']);
            }
        } catch (Exception $e) {

            $t->rollBack();

        }

    }

}