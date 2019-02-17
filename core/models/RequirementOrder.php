<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%requirement_order}}".
 *
 * @property integer $id
 * @property integer $store_id
 * @property integer $photographer_id
 * @property integer $requirement_id
 * @property integer $level_id
 * @property string $price
 * @property string $pay_price
 * @property integer $is_pay
 * @property integer $status
 * @property integer $addtime
 * @property integer $pay_time
 * @property integer $user_id
 * @property string $order_no
 * @property string $address
 * @property string $mobile
 * @property string $contact_name
 * @property string $datetime
 * @property integer $is_delete
 * @property string $contact_address,
 * @property integer $is_cancel
 * @property integer $access_time
 * @property integer $is_confirm
 * @property integer $confirm_time
 * @property string $lat
 * @property string $lon
 * @property integer $coupon_id
 * @property integer $amount
 * @property integer $taocan_id
 */
class RequirementOrder extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%requirement_order}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['store_id', 'photographer_id', 'requirement_id', 'level_id' , 'user_id', 'order_no','address','lat','lon'], 'required'],
            [['store_id', 'photographer_id', 'requirement_id', 'level_id', 'is_pay', 'status', 'addtime', 'pay_time', 'user_id','is_delete','is_cancel','confirm_time','is_confirm','coupon_id','amount','taocan_id'], 'integer'],
            [['price', 'pay_price'], 'number'],
            [['order_no', 'mobile', 'contact_name','datetime'], 'string', 'max' => 45],
            [['address','contact_address'], 'string', 'max' => 255],
            [['pay_time','lat','lon'],'default','value'=>0],
             [['pay_time','mobile','contact_name','is_delete','is_cancel','access_time','confirm_time','is_confirm'],'default','value'=>'0']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'store_id' => 'STORE_ID',
            'photographer_id' => '摄影师id',
            'requirement_id' => 'Requirement ID',
            'level_id' => '摄影师等级id',
            'price' => '价格',
            'pay_price' => '支付价格',
            'is_pay' => '支付',
            'status' => '0、待付款  1、已付款 2、完成',
            'addtime' => 'addtime',
            'pay_time' => 'Pay Time',
            'user_id' => 'user_id',
            'order_no' => '订单号',
            'address' => '拍摄地址',
            'mobile' => '联系电话',
            'contact_name' => '联系人',
            'datetime'=>'日期',
            'access_time'=>'日期',
            'contact_address'=>'联系地址',
            'is_delete'=>'是否删除','is_cancel'=>'是否取消',
            'confirm_time'=>'确认时间',
            'is_confirm'=>'是否完成',
            'lat'=>'订单纬度',
            'lon'=>'订单经度',
            'coupon_id'=>'优惠券id',
            'amount'=>'数量',
            'taocan_id'=>'taocan_id'
        ];
    }
}
