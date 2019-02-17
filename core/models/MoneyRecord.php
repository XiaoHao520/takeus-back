<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%money_record}}".
 *
 * @property integer $id
 * @property integer $store_id
 * @property string $price
 * @property integer $type
 * @property integer $user_id
 * @property integer $addtime
 * @property string $detail
 * @property integer $status
 */
class MoneyRecord extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%money_record}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['store_id','user_id', 'addtime','type','status'], 'integer'],
            [['detail'], 'string'],
            [['price'],'number']
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
            'user_id' => '用户ID',
            'price' => '钱',
            'type' => '类型',
            'addtime' => 'Addtime',
            'detail'=>'详情',
            'status'=>'进出'
        ];
    }

}
