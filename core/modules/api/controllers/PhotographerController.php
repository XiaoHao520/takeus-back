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
use app\models\FormId;
use app\models\Label;
use app\models\Level;
use app\models\MoneyMsg;
use app\models\Option;
use app\models\Order;
use app\models\OrderMsg;
use app\models\Photographer;
use app\models\PhotographerLevel;
use app\models\ProductList;
use app\models\Requirement;
use app\models\RequirementOrder;
use app\models\Reward;
use app\models\Setting;
use app\models\Share;
use app\models\Store;
use app\models\Topic;
use app\models\User;
use app\models\UserAuthLogin;
use app\models\UserCard;
use app\models\UserCenterForm;
use app\models\UserCenterMenu;
use app\models\UserFormId;
use app\models\WechatTplMsgSender;
use app\modules\api\behaviors\LoginBehavior;
use app\modules\api\models\AddressDeleteForm;
use app\modules\api\models\AddressSaveForm;
use app\modules\api\models\AddressSetDefaultForm;
use app\modules\api\models\AddWechatAddressForm;
use app\modules\api\models\CardListForm;
use app\modules\api\models\FavoriteAddForm;
use app\modules\api\models\FavoriteListForm;
use app\modules\api\models\FavoriteRemoveForm;
use app\modules\api\models\OrderListForm;
use app\modules\api\models\OrderReceiveForm;
use app\modules\api\models\PhotographerForm;
use app\modules\api\models\PhotographerListForm;
use app\modules\api\models\TopicFavoriteForm;
use app\modules\api\models\TopicFavoriteListForm;
use app\modules\api\models\WechatDistrictForm;
use app\modules\api\models\QrcodeForm;
use app\modules\api\models\OrderMemberForm;
use app\models\SmsSetting;
use app\modules\api\models\UserForm;
use app\extensions\Sms;
use function MongoDB\BSON\fromJSON;

class PhotographerController extends Controller
{


    public function actionIndex()
    {
        $photographer = Photographer::findOne(['user_id' => \Yii::$app->user->identity->id]);
        if ($photographer) {
            $form = new PhotographerForm();
            $form->photographer = $photographer;
            return new BaseApiResponse($form->search());
        } else {
            return new BaseApiResponse(['code' => 1, 'msg' => '你不是摄影师']);
        }
    }


    public function actionPhotographerLevel()
    {
        $level_list = PhotographerLevel::find()->where(['store_id' => $this->store_id, 'is_delete' => 0])->asArray()->all();



       if(count($level_list)){
           return new BaseApiResponse([
               'code' => 0,
               'data' => $level_list
           ]);
       }else{
           return new BaseApiResponse([
               'code' => 1,
               'msg' => '系统未设置摄影师级别'
           ]);
       }


    }

    public function actionAreaList()
    {
        $form = new PhotographerListForm();
        $u_lat = \Yii::$app->request->get('u_lat');
        $u_lon = \Yii::$app->request->get('u_lon');
        $form->store_id = $this->store_id;

        $form->u_lat = $u_lat;
        $form->u_lon = $u_lon;
        return new BaseApiResponse($form->search());

    }

    public function actionUpdateLocation()
    {
        $user_id = \Yii::$app->user->identity->id;
        $photographer = Photographer::findOne(['user_id' => $user_id, 'is_delete' => 0]);
        if ($photographer) {
            $lat = \Yii::$app->request->get('lat');
            $lon = \Yii::$app->request->get('lon');

            $photographer->lat = $lat;

            $photographer->lon = $lon;
            $photographer->address = \Yii::$app->request->get('address');

            $photographer->save();


            return new BaseApiResponse([
                'code' => 0,
                'msg' => '更新成功'

            ]);

        } else {
            return new BaseApiResponse([
                'code' => 0,
                'msg' => '更新失败'
            ]);

        }


    }


    public function actionApply()
    {


        $model = \Yii::$app->request->post();
        $user_id = \Yii::$app->user->identity->id;
        $model['user_id'] = $user_id;
        $photographer = Photographer::findOne(['user_id' => $user_id, 'is_delete' => 0]);
        if ($photographer) {
            return new BaseApiResponse([
                'code' => 1,
                'msg' => "你已经申请过了哦！请勿重复提交申请"
            ]);
        }

        $model['store_id'] = $this->store_id;
        $model['addtime'] = time();
        $model['is_delete'] = 0;
        $photographer = new Photographer();
        $photographer->attributes = $model;
        ProductList::updateAll(['is_delete' => 1], ['user_id' => $user_id]);
        $product_list=json_decode($model['product_list']);

        foreach ($product_list as $pic_url) {
            $product_pic = new ProductList();
            $product_pic->user_id = $user_id;
            $product_pic->pic_url = $pic_url;
            $product_pic->is_delete = 0;
            $product_pic->addtime=time();
            $product_pic->store_id=$this->store_id;
            $product_pic->save();
        }







        return new BaseApiResponse($photographer->savePhotographer());


    }

    public function getDistance($longitude1, $latitude1, $longitude2, $latitude2, $unit = 2, $decimal = 2)
    {

        $EARTH_RADIUS = 6370.996; // 地球半径系数
        $PI = 3.1415926;

        $radLat1 = $latitude1 * $PI / 180.0;
        $radLat2 = $latitude2 * $PI / 180.0;

        $radLng1 = $longitude1 * $PI / 180.0;
        $radLng2 = $longitude2 * $PI / 180.0;

        $a = $radLat1 - $radLat2;
        $b = $radLng1 - $radLng2;

        $distance = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2)));
        $distance = $distance * $EARTH_RADIUS * 1000;

        if ($unit == 2) {
            $distance = $distance / 1000;
        }

        return round($distance, $decimal);

    }

    function get_distance($from, $to, $km = true, $decimal = 2)
    {
        sort($from);
        sort($to);
        $EARTH_RADIUS = 6370.996;
        $distance = $EARTH_RADIUS * 2 * asin(sqrt(pow(sin(($from[0] * pi() / 180 - $to[0] * pi() / 180) / 2), 2) + cos($from[0] * pi() / 180) * cos($to[0] * pi() / 180) * pow(sin(($from[1] * pi() / 180 - $to[1] * pi() / 180) / 2), 2))) * 1000;
        if ($km) {
            $distance = $distance / 1000;
        }
        return round($distance, $decimal);
    }

    public function actionHideLocation()
    {


        $photographer = Photographer::findOne(['user_id' => \Yii::$app->user->identity->id]);
        $hide = \Yii::$app->request->get('hide');

        if ($photographer) {
            $photographer->is_hide = $hide;


            if ($photographer->save()) {
                if ($hide) {
                    return new BaseApiResponse(['code' => 0, 'msg' => '隐藏成功，其他用户无法看到您的位置']);
                } else {
                    return new BaseApiResponse(['code' => 0, 'msg' => '开启成功']);
                }

            } else {
                return new BaseApiResponse(['code' => 1, 'msg' => '找不到该摄影师']);

            }

        }


    }


    public function actionInfo()
    {
        $p_id = \Yii::$app->request->get('p_id');
        $m_lat = \Yii::$app->request->get('m_lat');
        $m_lon = \Yii::$app->request->get('m_lon');
        $photographer = Photographer::findOne($p_id);

        if ($photographer) {

            $from = array($m_lat, $m_lon);
            $to = array($photographer->lat, $photographer->lon);
            $distance = $this->get_distance($from, $to, true, 2);

            $photographer = $photographer->toArray();
            $photographer['distance'] = $distance;
            $label_list = Label::find()->where(['user_id' => $photographer['user_id'], 'is_delete' => 0])->asArray()->all();
            return new BaseApiResponse(['code' => 0, 'data' => $photographer, 'label_list' => $label_list]);
        } else {
            return new BaseApiResponse(['code' => 1, 'msg' => '找不到该摄影师']);

        }

    }


    public function actionMyInfo()
    {

        $photographer = Photographer::findOne(['user_id' => \Yii::$app->user->identity->id, 'is_delete' => 0]);
        if ($photographer) {
            $label_list = Label::find()->where(['user_id' => $photographer->user_id, 'is_delete' => 0])->asArray()->all();


            return new BaseApiResponse(['code' => 0, 'data' => $photographer, 'label_list' => $label_list]);
        } else {
            return new BaseApiResponse(['code' => 1, 'msg' => '你还不是摄影师，是否申请成为摄影师？']);
        }
    }

    public function actionAddLabel()
    {

        $user_id = \Yii::$app->user->identity->id;
        $label_list = Label::find()->where(['user_id' => $user_id, 'is_delete' => 0])->asArray()->all();
        if (count($label_list) < 10) {
            $label = new Label();
            $label->user_id = $user_id;
            $label->store_id = $this->store_id;
            $label->addtime = time();
            $label->is_delete = 0;
            $label->content = \Yii::$app->request->get('content');
            if ($label->save()) {
                $label_list = Label::find()->where(['user_id' => $user_id, 'is_delete' => 0])->asArray()->all();
                return new BaseApiResponse(['code' => 0, 'label_list' => $label_list]);
            } else {
                return new BaseApiResponse(['code' => 1, 'msg' => '系统错误', 'error' => $label->errors]);
            }


        } else {
            return new BaseApiResponse(['code' => 1, 'msg' => '你累计添加了是个标签，不可再继续添加']);

        }

    }

    public function actionOrderList()
    {
        $user_id = \Yii::$app->user->identity->id;
        $photographer = Photographer::findOne(['user_id' => $user_id]);
        $status = \Yii::$app->request->get('status');

        if ($photographer) {
            $query = RequirementOrder::find()->alias('ro')->where([
                'ro.is_delete' => 0,
                'ro.store_id' => $this->store_id,
                'ro.is_cancel' => 0,
                'ro.status' => $status,
                'ro.is_pay' => 1,
                'ro.photographer_id' => $photographer->id
            ]);
            $query->leftJoin(Photographer::tableName() . 'p', 'p.id=ro.photographer_id')
                ->leftJoin(PhotographerLevel::tableName() . 'pl', 'pl.id=p.level_id')
                ->leftJoin(User::tableName() . 'u', 'u.id=ro.user_id')
                ->leftJoin(Requirement::tableName() . 'r', 'r.id=ro.requirement_id');
            $query->select('ro.*,r.name as r_name,pl.name as l_name,p.name as p_name,p.header_url as pic_url,u.nickname');
            $sql = $query->createCommand()->rawSql;
            $list = $query->asArray()->all();
            $count = $query->count();
            if ($count) {
                return new BaseApiResponse(['code' => 0, 'data' => ['order_list' => $list, 'count' => $count]]);
            } else {
                return new BaseApiResponse(['code' => 1, 'msg' => '暂无数据', 'sql' => $sql]);
            }
        } else {
            return new BaseApiResponse(['code' => 1, 'msg' => '你不是摄影师']);
        }
    }


    //    短信验证是否开启
    public function actionSmsSetting()
    {
        $sms_setting = SmsSetting::findOne(['is_delete' => 0, 'store_id' => $this->store->id]);
        if ($sms_setting->status == 1) {
            return new BaseApiResponse([
                'code' => 0,
                'data' => $sms_setting->status
            ]);
        } else {
            return new BaseApiResponse([
                'code' => 1,
                'data' => $sms_setting->status
            ]);
        }
    }

    public function actionAccessOrder()
    {
        $order_id = \Yii::$app->request->get('order_id');
        $order = RequirementOrder::findOne(['id' => $order_id, 'status' => 1, 'is_pay' => 1]);


        if ($order) {
            $order->access_time = time();
            $order->status = 2;

            if ($order->save()) {

                $photographer = Photographer::findOne($order->photographer_id);
                if ($photographer) {
                    $msg = new OrderMsg();
                    $msg->store_id = $this->store_id;
                    $msg->user_id = $order->user_id;
                    $msg->detail = '摄影师：' . $photographer->name . ' 已接单';
                    $msg->addtime = time();
                    $msg->order_id = $order->id;
                    $msg->save();
                }
                    $receive=new OrderReceiveForm();

                    $receive->order_id=$order->id;
                    $receive->store_id=$this->store_id;
                    $receive->save();


                return new BaseApiResponse([
                    'code' => 0,
                    'msg' => '接单成功'
                ]);
            } else {
                return new BaseApiResponse([
                    'code' => 1,
                    'msg' => '接单失败'
                ]);
            }


        } else {
            return new BaseApiResponse([
                'code' => 1,
                'msg' => '找不到该订单'
            ]);
        }
    }


    public function actionCanDown()
    {

        $order_id = \Yii::$app->request->get('order_id');
        $order = RequirementOrder::findOne($order_id);
        $order->status = 3;
        $order->save();
        return new BaseApiResponse([
            'code' => 0,
            'msg' => '完成'
        ]);


    }

    public function actionMsg()
    {

        $order_msg = $this->orderMsg();
        return new BaseApiResponse(['code' => 0, 'data' => [
            'order_msg' => $order_msg,
            'topic_msg' => $this->topicMsg(),
            'money_msg' => $this->moneyMsg()
        ]]);


    }

    private function orderMsg()
    {

        $user_id = \Yii::$app->user->identity->id;


        $photographer = Photographer::findOne(['user_id' => $user_id]);
        if ($photographer) {

            $order_list = OrderMsg::find()->where(['user_id' => $user_id, 'is_delete' => 0,'is_read'=>0])->asArray()->all();
            $count = count($order_list);
            if ($count > 0) {
                $order = $order_list[0];
                $detail = $order['detail'];

            } else {
                $detail = "暂无新得订单消息";
            }


            return ['detail' => $detail, 'code' => 0, 'count' => $count];

        } else {

            return ['code' => 0, 'msg' => '你不是摄影师'];
        }

    }

    private function topicMsg()
    {

        $user_id = \Yii::$app->user->identity->id;
        $photographer = Photographer::findOne(['user_id' => $user_id, 'is_delete' => 0, 'status' => 1]);
        $query = Topic::find()->where(['store_id' => $this->store_id, 'is_delete' => 0]);
        if ($photographer) {
            $query->andWhere(['<', 'target', 2]);
        } else {
            $query->andWhere(['<>', 'target', 1]);
        }

        $topic_list = $query->orderBy('addtime DESC')->asArray()->all();
        if (count($topic_list) > 0) {
            $topic = $topic_list[0];

            $detail = $topic['title'];
        } else {
            $detail = '暂无文章';
        }

        return ['detail' => $detail, 'code' => 0, 'count' => count($topic_list)];
    }

    private function moneyMsg()
    {
        $user_id = \Yii::$app->user->identity->id;

        $msg_list = MoneyMsg::find()->where(['is_read' => 0, 'user_id' => $user_id])->orderBy('addtime DESC')->asArray()->all();
        if (count($msg_list) > 0) {
            $msg = $msg_list[0];

            $detail = $msg['detail'];
        } else {
            $detail = '暂无消息';
        }

        return ['detail' => $detail, 'code' => 0, 'count' => count($msg_list)];


    }


    public function actionAddPrice()
    {
        $order_id = \Yii::$app->request->get('order_id');
        $price = \Yii::$app->request->get('price');
        $order = RequirementOrder::findOne(['id' => $order_id, 'is_pay' => 1, 'is_delete' => 0, 'status' => 2]);
        if ($order) {
            $reward = Reward::findOne(['order_id' => $order_id, 'is_pay' => 0, 'is_delete' => 0]);
            if (!$reward) {
                $reward = new Reward();
                $reward->store_id = $this->store_id;
                $reward->order_id = $order_id;
                $reward->price = $price;
                $reward->order_no = $this->getOrderNo();
                $reward->addtime = time();
                if ($reward->save()) {

                    $photographer = Photographer::findOne($order->photographer_id);
                    if ($photographer) {
                        $msg = new OrderMsg();
                        $msg->store_id = $this->store_id;
                        $msg->user_id = $order->user_id;
                        $msg->detail = '摄影师：' . $photographer->name . ' 增加了 ' . $price . '元服务费';
                        $msg->addtime = time();
                        $msg->order_id = $order->id;
                        $msg->save();
                    }


                    return new BaseApiResponse([
                        'code' => 0,
                        'msg' => '操作成功'
                    ]);

                } else {
                    return new BaseApiResponse([
                        'code' => 1,
                        'msg' => '操作失败'
                    ]);
                }
            } else {
                return new BaseApiResponse([
                    'code' => 1,
                    'msg' => '您已经增加过费用，请删除后重试'
                ]);
            }


        } else {
            return new BaseApiResponse([
                'code' => 1,
                'msg' => '找不到该订单'
            ]);
        }


    }

    private function getOrderNo()
    {
        $store_id = empty($this->store_id) ? 0 : $this->store_id;
        $order_no = null;
        while (true) {
            $order_no = "R" . date('YmdHis') . rand(100000, 999999);
            $exist_order_no = Reward::find()->where(['order_no' => $order_no])->exists();
            if (!$exist_order_no)
                break;
        }
        return $order_no;
    }


}