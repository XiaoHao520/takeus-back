<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%money_msg}}".
 *
 * @property integer $id
 * @property integer $store_id
 * @property integer $user_id
 * @property integer $from_id
 * @property string $detail
 * @property integer $addtime
 * @property integer $is_read
 * @property integer $type
 * @property string $price
 */
class MoneyMsg extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%money_msg}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['store_id', 'user_id', 'detail', 'addtime'], 'required'],
            [['store_id', 'user_id', 'from_id', 'addtime', 'is_read', 'type'], 'integer'],
            [['price'], 'number'],
            [['detail'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'store_id' => 'Store ID',
            'user_id' => 'User ID',
            'from_id' => 'From ID',
            'detail' => 'Detail',
            'addtime' => 'Addtime',
            'is_read' => 'Is Read',
            'type' => '0 普通订单   1 增加费用',
            'price' => 'Price',
        ];
    }
}
