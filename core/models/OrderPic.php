<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%order_pic}}".
 *
 * @property integer $id
 * @property integer $store_id
 * @property string $url
 * @property integer $is_delete
 * @property integer $addtime
 * @property integer $order_id
 * @property integer $is_compress
 */
class OrderPic extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%order_pic}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['store_id', 'url', 'addtime', 'order_id'], 'required'],
            [['store_id', 'is_delete', 'addtime', 'order_id','is_compress'], 'integer'],
            [['url'], 'string', 'max' => 255],
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
            'url' => 'Url',
            'is_delete' => 'Is Delete',
            'addtime' => 'Addtime',
            'order_id' => 'Order ID',
            'is_compress'=>'是否压缩'
        ];
    }
}
