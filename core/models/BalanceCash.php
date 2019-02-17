<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%balance_cash}}".
 *
 * @property integer $id
 * @property integer $user_id
 * @property integer $store_id
 * @property string $price
 * @property integer $status
 * @property integer $is_delete
 * @property integer $addtime
 * @property integer $pay_time
 * @property string $order_no
 * @property string $real_price
 * @property string $name
 * @property string $mobile
 * @property integer $is_down
 */
class BalanceCash extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%balance_cash}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'store_id', 'addtime', 'pay_time', 'order_no', 'name', 'mobile'], 'required'],
            [['user_id', 'store_id', 'status', 'is_delete', 'addtime', 'pay_time', 'is_down'], 'integer'],
            [['price', 'real_price'], 'number'],
            [['order_no'], 'string', 'max' => 100],
            [['name', 'mobile'], 'string', 'max' => 45],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'store_id' => 'Store ID',
            'price' => 'Price',
            'status' => '申请状态 0--申请中 1--确认申请 2--已打款 3--驳回  5--余额通过',
            'is_delete' => 'Is Delete',
            'addtime' => 'Addtime',
            'pay_time' => 'Pay Time',
            'order_no' => '微信打款订单号',
            'real_price' => 'Real Price',
            'name' => 'Name',
            'mobile' => 'Mobile',
            'is_down' => 'Is Down',
        ];
    }
}
