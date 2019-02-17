<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%order_msg}}".
 *
 * @property integer $id
 * @property integer $store_id
 * @property integer $user_id
 * @property integer $order_id
 * @property integer $addtime
 * @property integer $is_delete
 * @property string $detail
 * @property integer $is_read
 */
class OrderMsg extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%order_msg}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['store_id', 'user_id', 'order_id', 'addtime', 'detail'], 'required'],
            [['store_id', 'user_id', 'order_id', 'addtime', 'is_delete','is_read'], 'integer'],
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
            'order_id' => 'Order ID',
            'addtime' => 'Addtime',
            'is_delete' => 'Is Delete',
            'detail' => 'Detail',
            'is_read'=>'is_read'
        ];
    }
}
