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
use app\models\BalanceCash;
use app\models\CouponMsg;
use app\models\FormId;
use app\models\Level;
use app\models\MoneyRecord;
use app\models\Online;
use app\models\Option;
use app\models\Order;
use app\models\OrderMsg;
use app\models\Photographer;
use app\models\RequirementOrder;
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
use app\modules\api\behaviors\LoginBehavior;
use app\modules\api\models\AddressDeleteForm;
use app\modules\api\models\AddressSaveForm;
use app\modules\api\models\AddressSetDefaultForm;
use app\modules\api\models\AddWechatAddressForm;
use app\modules\api\models\BalanceCashForm;
use app\modules\api\models\BalanceCashListForm;
use app\modules\api\models\CardListForm;
use app\modules\api\models\FavoriteAddForm;
use app\modules\api\models\FavoriteListForm;
use app\modules\api\models\FavoriteRemoveForm;
use app\modules\api\models\OrderListForm;
use app\modules\api\models\TopicFavoriteForm;
use app\modules\api\models\TopicFavoriteListForm;
use app\modules\api\models\WechatDistrictForm;
use app\modules\api\models\QrcodeForm;
use app\modules\api\models\OrderMemberForm;
use app\models\SmsSetting;
use app\modules\api\models\UserForm;
use app\extensions\Sms;

class UserController extends Controller
{
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            'login' => [
                'class' => LoginBehavior::className(),
            ],
        ]);
    }

    //个人中心
    public function actionIndex()
    {
        $order_count = OrderListForm::getCountData($this->store->id, \Yii::$app->user->id);
        $share_setting = Setting::find()->where(['store_id' => $this->store->id])->asArray()->one();
        $parent = User::findOne(\Yii::$app->user->identity->parent_id);
        $share = Share::findOne(['user_id' => \Yii::$app->user->identity->parent_id]);

        $user = User::findOne(['id' => \Yii::$app->user->identity->id]);
        $level = $user->level;


        $now_level = Level::findOne(['store_id' => $this->store->id, 'level' => $level, 'is_delete' => 0]);
        $user_info = [
            'nickname' => \Yii::$app->user->identity->nickname,
            'binding' => $user->binding,
            'avatar_url' => \Yii::$app->user->identity->avatar_url,
            'is_distributor' => \Yii::$app->user->identity->is_distributor,
//            'parent' => $share ? $share->name : ($parent ? $parent->nickname : '总店'),
            'parent' => $share ? ($share->name ? $share->name : $parent->nickname) : "总店",
            'id' => \Yii::$app->user->identity->id,
            'is_clerk' => \Yii::$app->user->identity->is_clerk,
            'level' => $level,
            'level_name' => $now_level ? $now_level->name : "普通用户",
            'integral' => \Yii::$app->user->identity->integral,
            'money' => \Yii::$app->user->identity->money,
            'is_photographer' => \Yii::$app->user->identity->is_photographer
        ];
        $next_level = Level::find()->where(['store_id' => $this->store->id, 'is_delete' => 0, 'status' => 1])
            ->andWhere(['>', 'level', $level])->orderBy(['level' => SORT_ASC, 'id' => SORT_DESC])->asArray()->one();

        //余额功能配置
        $balance = Option::get('re_setting', $this->store->id, 'app');
        $balance = json_decode($balance, true);
        //我的钱包 选项
        $wallet['integral'] = 1;
        if ($balance) {
            $wallet['re'] = $balance['status'];
        }

        /* 旧版的菜单列表先保留以兼容旧版，后期去掉 */
        $user_center_menu = new UserCenterMenu();
        $user_center_menu->store_id = $this->store->id;

        $user_center_form = new UserCenterForm();
        $user_center_form->store_id = $this->store->id;
        $user_center_form->user_id = $user->id;
        $user_center_data = $user_center_form->getData();
        $user_center_data = $user_center_data['data'];
        $wallet['is_wallet'] = $user_center_data['is_wallet'];
        $photographer = Photographer::findOne(['user_id' => $user->id, 'is_delete' => 0]);
        $is_photopgrapher = 0;
        $is_hide = 0;
        if ($photographer) {
            $is_photopgrapher = 1;
            $is_hide = $photographer->is_hide;
        }
        $user_info['is_photographer'] = $is_photopgrapher;
        $user_info['is_hide'] = $is_hide;


        $menus = $user_center_data['menus'];


        $user_id = \Yii::$app->user->identity->id;
        $photographer = Photographer::findOne(['user_id' => $user_id]);
        if ($photographer && $photographer->status == 1) {

            $name = "摄影中心";

            $this->updateOnline(strtotime(date('Y-m-d 00:00:00')), strtotime(date('Y-m-d 23:59:59')));

        } else {
            $name = "成为摄影师";
        }


        foreach ($menus as $i => $item) {
            if ($item['id'] == 'photographer') {
                $menus[$i]['name'] = $name;

            }
        }


        return new BaseApiResponse([
            'code' => 0,
            'msg' => 'success',
            'data' => [
                'order_count' => $order_count,
                'show_customer_service' => $this->store->show_customer_service,
                'contact_tel' => $this->store->contact_tel,
                'share_setting' => $share_setting,
                'user_info' => $user_info,
                'next_level' => $next_level,
                'menu_list' => $user_center_menu->getMenuList(),
                'user_center_bg' => $user_center_data['user_center_bg'],
                'orders' => $user_center_data['orders'],
                'menus' => $menus,
                'copyright' => $user_center_data['copyright'],
                'wallet' => $wallet,

                'style' => [
                    'menu' => $user_center_data['menu_style'],
                    'top' => $user_center_data['top_style'],
                ],
                'setting' => [
                    'is_order' => $user_center_data['is_order']
                ]
            ],
        ]);
    }

    private function updateOnline($start, $end)
    {
        $user_id = \Yii::$app->user->identity->id;
        $query = Online::find()->where([
            'store_id' => $this->store_id,
            'user_id' => $user_id,

        ]);
        if (is_int($start)) {
            $query->andWhere(['>=', 'addtime', $start]);
        }
        if (is_int($end)) {
            $query->andWhere(['<=', 'addtime', $end]);
        }
        $online = $query->one();
        if ($online) {

        } else {
            $photographer = Photographer::findOne(['user_id' => $user_id, 'status' => 1]);
            if ($photographer) {
                $online = new Online();
                $online->store_id = $this->store_id;
                $online->user_id = $user_id;
                $online->start = time();
                $online->addtime = time();
                $online->save();

            }
        }
    }


    public function actionMsgList()
    {

        $order_msg = $this->orderMsg();
        $coupon_msg = $this->couponMsg();
        $topic_msg = $this->topicMsg();

        return new BaseApiResponse(['code' => 0, 'data' => [
            'order_msg' => $order_msg,
            'topic_msg' => $topic_msg,
            'coupon_msg' => $coupon_msg,
            'all_count' => $coupon_msg['count'] + $order_msg['count'] + $topic_msg['coupon']

        ]]);

    }


    private function orderMsg()
    {
        $user_id = \Yii::$app->user->identity->id;


        $order_list = OrderMsg::find()->where(['user_id' => $user_id, 'is_delete' => 0, 'store_id' => $this->store->id, 'is_read' => 0])
            ->orderBy('addtime DESC')->asArray()->all();


        if (count($order_list) > 0) {
            $order = $order_list[0];

            $detail = $order['detail'];
        } else {
            $detail = '暂无消息';
        }

        return ['detail' => $detail, 'code' => 0, 'count' => count($order_list)];


    }

    private function couponMsg()
    {
        $user_id = \Yii::$app->user->identity->id;
        $coupon_list = CouponMsg::find()->where(['user_id' => $user_id, 'is_use' => 0, 'is_read' => 0])->asArray()->all();
        if ($coupon_list) {
            $coupon_msg = $coupon_list[0];
        } else {
            $coupon_msg = 0;
        }

        return ['detail' => '优惠券', 'code' => 0, 'count' => count($coupon_list), 'data' => $coupon_msg];
    }


    private function topicMsg()
    {

        $query = Topic::find()->where(['store_id' => $this->store_id, 'is_delete' => 0]);
        $query->andWhere(['<>', 'target', 1]);
        $topic_list = $query->orderBy('addtime DESC')->asArray()->all();
        if (count($topic_list) > 0) {
            $topic = $topic_list[0];

            $detail = $topic['title'];
        } else {
            $detail = '暂无文章';
        }

        return ['detail' => $detail, 'code' => 0, 'count' => count($topic_list)];


    }


    /**
     * @return mixed|string
     * 获取提现相关
     */
    public function actionGetPrice()
    {

        $store = Store::findOne($this->store_id);
        $res['data']['price'] = \Yii::$app->user->identity->money;
        $setting = Setting::findOne(['store_id' => $this->store->id]);
        $res['data']['pay_type'] = $setting->pay_type;
        $res['data']['bank'] = $setting->bank;
        $res['data']['remaining_sum'] = $setting->remaining_sum;
        $res['data']['rate'] = $store->rate;
        $cash_last = BalanceCash::find()->where(['store_id' => $this->store->id, 'user_id' => \Yii::$app->user->identity->id, 'is_delete' => 0])
            ->orderBy(['id' => SORT_DESC])->asArray()->one();
        $res['data']['cash_last'] = $cash_last;
        return new ApiResponse(0, 'success', $res['data']);
    }

    /**
     * @return mixed|string
     * 申请提现
     */
    public function actionApply()
    {
        $form = new BalanceCashForm();
        $form->user_id = \Yii::$app->user->identity->id;
        $form->store_id = $this->store_id;
        $form->attributes = \Yii::$app->request->post();
        $form->name = \Yii::$app->request->post('name');
        $form->mobile = \Yii::$app->request->post('mobile');
        return new BaseApiResponse($form->save());
    }


    /**
     * 提现明细列表
     */
    public function actionCashDetail()
    {
        $form = new BalanceCashListForm();
        $get = \Yii::$app->request->get();
        $form->attributes = $get;
        $form->store_id = $this->store->id;
        $form->user_id = \Yii::$app->user->id;
        return $form->getList();
    }

    public function actionWalletDetail()
    {

        $query = MoneyRecord::find()->where(['store_id' => $this->store_id, 'user_id' => \Yii::$app->user->identity->id]);

        $record = $query->orderBy('addtime DESC')->asArray()->all();


        foreach ($record as $index => $item) {
            $item['addtime'] = date("Y-m-d H:i:s", $item['addtime']);
            $record[$index] = $item;
        }


        return new BaseApiResponse([
            'code' => 0,
            'data' => ['record_list' => $record],
            //  "msg" => $query->createCommand()->rawSql

        ]);
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

    //    绑定手机号
    public function actionUserBinding()
    {
        $form = new UserForm();
        $form->attributes = \Yii::$app->request->post();
        $form->wechat_app = $this->wechat_app;
        $form->user_id = \Yii::$app->user->identity->id;
        $form->store_id = $this->store->id;
        return new BaseApiResponse($form->binding());
    }

    //    短信验证
    public function actionUserHandBinding()
    {
        $form = new Sms();
        $form->attributes = \Yii::$app->request->post();
        $code = mt_rand(0, 999999);
        return new BaseApiResponse($form->send_text($this->store->id, $code, $form->attributes['content']));
    }

    // 授权手机号确认
    public function actionUserEmpower()
    {
        $form = new UserForm();
        $form->attributes = \Yii::$app->request->post();
        $form->user_id = \Yii::$app->user->identity->id;
        $form->store_id = $this->store->id;
        return new BaseApiResponse($form->userEmpower());
    }

    //收货地址列表
    public function actionAddressList()
    {
        $list = Address::find()->select('id,name,mobile,province_id,province,city_id,city,district_id,district,detail,is_default')->where([
            'store_id' => $this->store->id,
            'user_id' => \Yii::$app->user->id,
            'is_delete' => 0,
        ])->orderBy('is_default DESC,addtime DESC')->asArray()->all();
        foreach ($list as $i => $item) {
            $list[$i]['address'] = $item['province'] . $item['city'] . $item['district'] . $item['detail'];
        }
        return new BaseApiResponse((object)[
            'code' => 0,
            'msg' => 'success',
            'data' => [
                'list' => $list,
            ],
        ]);
    }

    /**
     * 会员支付
     */
    public function actionSubmitMember()
    {
        $form = new OrderMemberForm();
        $form->store_id = $this->store->id;
        $form->user = \Yii::$app->user->identity;
        $form->attributes = \Yii::$app->request->post();
        return new BaseApiResponse($form->save());
    }

    //收货地址详情
    public function actionAddressDetail()
    {
        $address = Address::find()->select('id,name,mobile,province_id,province,city_id,city,district_id,district,detail,is_default')->where([
            'store_id' => $this->store->id,
            'user_id' => \Yii::$app->user->id,
            'is_delete' => 0,
            'id' => \Yii::$app->request->get('id'),
        ])->one();
        if (!$address) {
            return new BaseApiResponse([
                'code' => 1,
                'msg' => '收货地址不存在',
            ]);
        }
        return new BaseApiResponse((object)[
            'code' => 0,
            'msg' => 'success',
            'data' => [
                'address_id' => $address->id,
                'name' => $address->name,
                'mobile' => $address->mobile,
                'district' => [
                    'province' => [
                        'id' => $address->province_id,
                        'name' => $address->province,
                    ],
                    'city' => [
                        'id' => $address->city_id,
                        'name' => $address->city,
                    ],
                    'district' => [
                        'id' => $address->district_id,
                        'name' => $address->district,
                    ],
                ],
                'detail' => $address->detail,
            ],
        ]);
    }

    //保存收货地址
    public function actionAddressSave()
    {
        $form = new AddressSaveForm();
        $form->attributes = \Yii::$app->request->post();
        $form->store_id = $this->store->id;
        $form->user_id = \Yii::$app->user->id;
        return new BaseApiResponse($form->save());
    }

    //设为默认收货地址
    public function actionAddressSetDefault()
    {
        $form = new AddressSetDefaultForm();
        $form->attributes = \Yii::$app->request->get();
        $form->store_id = $this->store->id;
        $form->user_id = \Yii::$app->user->id;
        return new BaseApiResponse($form->save());
    }

    //删除收货地址
    public function actionAddressDelete()
    {
        $form = new AddressDeleteForm();
        $form->attributes = \Yii::$app->request->get();
        $form->store_id = $this->store->id;
        $form->user_id = \Yii::$app->user->id;
        return new BaseApiResponse($form->save());
    }


    //保存用户的form id
    public function actionSaveFormId()
    {
        if (!\Yii::$app->user->isGuest) {
            FormId::addFormId([
                'store_id' => $this->store->id,
                'user_id' => \Yii::$app->user->id,
                'wechat_open_id' => \Yii::$app->user->identity->wechat_open_id,
                'form_id' => \Yii::$app->request->get('form_id'),
                'type' => 'form_id',
            ]);
            UserFormId::saveFormId([
                'user_id' => \Yii::$app->user->id,
                'form_id' => \Yii::$app->request->get('form_id'),
            ]);
        }
        return new ApiResponse(0);
    }

    //添加商品到我的喜欢
    public function actionFavoriteAdd()
    {
        $form = new FavoriteAddForm();
        $form->attributes = \Yii::$app->request->post();
        $form->store_id = $this->store->id;
        $form->user_id = \Yii::$app->user->id;
        return new BaseApiResponse($form->save());
    }

    //取消喜欢商品
    public function actionFavoriteRemove()
    {
        $form = new FavoriteRemoveForm();
        $form->attributes = \Yii::$app->request->post();
        $form->store_id = $this->store->id;
        $form->user_id = \Yii::$app->user->id;
        return new BaseApiResponse($form->save());
    }

    //喜欢的商品列表
    public function actionFavoriteList()
    {
        $form = new FavoriteListForm();
        $form->attributes = \Yii::$app->request->get();
        $form->user_id = \Yii::$app->user->id;
        return new BaseApiResponse($form->search());
    }

    //根据微信地址获取数据库省市区数据
    public function actionWechatDistrict()
    {
        $form = new WechatDistrictForm();
        $form->attributes = \Yii::$app->request->get();
        return new BaseApiResponse($form->search());
    }

    //添加微信获取的地址
    public function actionAddWechatAddress()
    {
        $form = new AddWechatAddressForm();
        $form->attributes = \Yii::$app->request->post();
        $form->store_id = $this->store->id;
        $form->user_id = \Yii::$app->user->id;
        return new BaseApiResponse($form->save());
    }

    //收藏|取消收藏专题
    public function actionTopicFavorite()
    {
        $form = new TopicFavoriteForm();
        $form->attributes = \Yii::$app->request->get();
        $form->store_id = $this->store->id;
        $form->user_id = \Yii::$app->user->id;
        return new BaseApiResponse($form->save());
    }

    //收藏专题列表
    public function actionTopicFavoriteList()
    {
        $form = new TopicFavoriteListForm();
        $form->attributes = \Yii::$app->request->get();
        $form->store_id = $this->store->id;
        $form->user_id = \Yii::$app->user->id;
        return new BaseApiResponse($form->search());
    }

    //会员权益
    public function actionMember()
    {
        $level = \Yii::$app->user->identity->level;
        $money = \Yii::$app->user->identity->money;


        $list = Level::find()->select(['id', 'image', 'level', 'name', 'price', 'buy_prompt', 'detail'])->where(['store_id' => $this->store->id, 'is_delete' => 0])->andWhere(['<>', 'price', '0'])->andWhere(['>', 'level', $level])->orderBy('level asc')->asArray()->all();

        $now_level = Level::find()->where(['store_id' => $this->store->id, 'level' => $level, 'is_delete' => 0])->asArray()->one();
        $user_info = [
            'nickname' => \Yii::$app->user->identity->nickname,
            'avatar_url' => \Yii::$app->user->identity->avatar_url,
            'id' => \Yii::$app->user->identity->id,
            'level' => $level,
            'level_name' => $now_level ? $now_level['name'] : "普通用户"
        ];
        $time = time();
        $store = $this->store;
        $sale_time = $time - ($store->after_sale_time * 86400);
        $next_level = Level::find()->where(['store_id' => $this->store->id, 'is_delete' => 0, 'status' => 1])
            ->andWhere(['>', 'level', $level])->orderBy(['level' => SORT_ASC, 'id' => SORT_DESC])->asArray()->one();
        $order_money = Order::find()->where(['store_id' => $this->store->id, 'user_id' => \Yii::$app->user->identity->id, 'is_delete' => 0])
            ->andWhere(['is_pay' => 1, 'is_confirm' => 1])->andWhere(['<=', 'confirm_time', $sale_time])->select([
                'sum(pay_price)'
            ])->scalar();
        $percent = 100;
        $s_money = 0;
        if ($next_level) {
            $percent = round($order_money / $next_level['money'] * 100, 2);
            $s_money = round($next_level['money'] - $order_money, 2);
        }
        $content = $store->member_content;
        $order_money = $order_money ? $order_money : 0;
        return new BaseApiResponse([
            'code' => 0,
            'msg' => 'success',
            'data' => [
                'user_info' => $user_info,
                'next_level' => $next_level,
                'now_level' => $now_level,
                'order_money' => $order_money,
                'percent' => $percent,
                's_money' => $s_money,
                'money' => $money,
                'content' => $content,
                'list' => $list,
            ],
        ]);
    }

    /**
     * 用户卡券
     */
    public function actionCard()
    {
        $form = new CardListForm();
        $form->store_id = $this->store->id;
        $form->user_id = \Yii::$app->user->identity->id;
        $form->attributes = \Yii::$app->request->get();
        return new BaseApiResponse($form->search());
    }

    /**
     * 卡券二维码
     */
    public function actionCardQrcode()
    {
        $user_card_id = \Yii::$app->request->get('user_card_id');
        $user_card = UserCard::findOne(['id' => $user_card_id]);
        $form = new QrcodeForm();
        $form->data = [
            'scene' => "{$user_card_id}",
            'page' => 'pages/card-clerk/card-clerk',
            'width' => 100
        ];
        $form->store = $this->store;
        $res = $form->getQrcode();
        return new BaseApiResponse($res);
    }

    /**
     * 卡券核销
     */
    public function actionCardClerk()
    {
        $user_card_id = \Yii::$app->request->get('user_card_id');
        if (\Yii::$app->cache->get('card_id_' . $user_card_id)) {
            return new BaseApiResponse([
                'code' => 1,
                'msg' => '卡券核销中，请稍后重试'
            ]);
        }
        \Yii::$app->cache->set('card_id_' . $user_card_id, true);
        $user_card = UserCard::findOne(['id' => $user_card_id]);
        if ($user_card->is_use != 0) {
            return new BaseApiResponse([
                'code' => 1,
                'msg' => '卡券已核销'
            ]);
        }
        $user = \Yii::$app->user->identity;
        if ($user->is_clerk != 1) {
            return new BaseApiResponse([
                'code' => 1,
                'msg' => '不是核销员禁止核销'
            ]);
        }
        $user_card->clerk_id = $user->id;
        $user_card->shop_id = $user->shop_id;
        $user_card->clerk_time = time();
        $user_card->is_use = 1;
        if ($user_card->save()) {
            \Yii::$app->cache->set('card_id_' . $user_card_id, false);
            return new BaseApiResponse([
                'code' => 0,
                'msg' => '核销成功'
            ]);
        } else {
            return new BaseApiResponse([
                'code' => 1,
                'msg' => '核销失败'
            ]);
        }
    }

    public function actionWebLogin($token)
    {
        $m = UserAuthLogin::findOne([
            'token' => $token,
            'store_id' => $this->store->id,
        ]);
        if (!$m) {
            return new BaseApiResponse([
                'code' => 1,
                'msg' => '错误的小程序码，请刷新网页重试'
            ]);
        }
        if ($m->is_pass != 0) {
            return new BaseApiResponse([
                'code' => 1,
                'msg' => '您已处理过，请勿重复提交'
            ]);
        }
        $m->user_id = \Yii::$app->user->id;
        $m->is_pass = 1;
        $m->save();
        return new BaseApiResponse([
            'code' => 0,
            'msg' => '已确认登录'
        ]);
    }
}