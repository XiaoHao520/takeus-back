<?php
/**
 * Created by IntelliJ IDEA.
 * User: luwei
 * Date: 2017/7/17
 * Time: 11:48
 */

namespace app\modules\api\models;


use app\models\FormId;
use app\models\RequirementOrder;
use app\models\User;
use app\models\UserFormId;

class ReOrderSubmitForm extends OrderData
{
    public $store_id;
    public $user_id;
    public $level_id;
    public $requirement_id;
    public $photographer_id;
    public $price;
    public $datetime;
    public $address;
    public $lat;
    public $lon;
    public $form_id;
    public $amount;
  public $taocan_id;

    public function rules()
    {
        return [
            [['requirement_id', 'level_id', 'photographer_id', 'price', 'datetime'], 'required'],
            [['requirement_id', 'level_id', 'photographer_id','amount','taocan_id'], 'integer'],
            [['price', 'lat', 'lon'], 'number'],
            [['datetime', 'address', 'form_id'], 'string']

        ];
    }

    public function attributeLabels()
    {
        return [
            'level_id' => 'level_id',
            'requiremenet_id' => 'r_id',
            'photographer_id' => 'p_id',
            'price' => 'price',
            'datetime' => 'datetime',
            'address' => '联系地址',
            'lat' => 'lat',
            'lon' => 'lon',
            'form_id' => 'form_id',
            'amount'=>'数量',
            'taocan_id'=>'套餐'
        ];
    }

    public function save()
    {
        if (!$this->validate())
            return $this->errorResponse;
        $order = new RequirementOrder();
        $order->attributes = $this->attributes;
        $order->store_id = $this->store_id;
        $order->user_id = $this->user_id;
        $order->addtime = time();
        $order->order_no = $this->getOrderNo();


        if ($order->save()) {
            $user = User::findOne($this->user_id);
          /*  FormId::addFormId([
                'store_id' => $this->store_id,
                'user_id' => $this->user_id,
                'wechat_open_id' => $user->wechat_open_id,
                'form_id' => $this->form_id,
                'type' => 'form_id',
                'order_no' => $order->order_no,
            ]);*/

            UserFormId::saveFormId([
                'user_id' => $this->user_id,
                'form_id' => $this->form_id,
            ]);


            return [
                'code' => '0',
                'order_id' => $order->attributes['id'],
                'msg' => '保存成功',
            ];

        } else {
            return [
                'code' => '1',
                'error' => $order->errors,
                'msg' => '保存失败',
            ];
        }


    }

    public function getOrderNo()
    {
        $store_id = empty($this->store_id) ? 0 : $this->store_id;
        $order_no = null;
        while (true) {
            $order_no = 'S' . date('YmdHis') . rand(100000, 999999);
            $exist_order_no = RequirementOrder::find()->where(['order_no' => $order_no])->exists();
            if (!$exist_order_no)
                break;
        }
        return $order_no;
    }


}