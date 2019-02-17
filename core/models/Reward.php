<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%reward}}".
 *
 * @property integer $id
 * @property integer $store_id
 * @property integer $order_id
 * @property integer $addtime
 * @property integer $pay_time
 * @property integer $is_pay
 * @property string $price
 * @property integer $is_delete
 * @property string $order_no
 * @property integer $is_cancel
 * @property integer $status
 */
class Reward extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%reward}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['store_id'], 'required'],
            [['store_id', 'order_id', 'addtime', 'pay_time', 'is_pay', 'is_delete','is_cancel','status'], 'integer'],
            [['price'], 'number'],
            [['order_no'], 'string', 'max' => 45],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'store_id' => 'store_id',
            'order_id' => '订单ID',
            'addtime' => '添加时间',
            'pay_time' => '支付时间',
            'is_pay' => '0 、未打款 1、已打款',
            'price' => 'Price',
            'is_delete' => 'Is Delete',
            'order_no' => 'Order No',
            'is_cancel'=>'is_cancel',
            'status'=>'是否打款给摄影师'
        ];
    }

}
