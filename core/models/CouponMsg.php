<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%coupon_msg}}".
 *
 * @property integer $id
 * @property integer $store_id
 * @property integer $user_id
 * @property integer $addtime
 * @property integer $is_use
 * @property integer $is_read
 */
class CouponMsg extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%coupon_msg}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['store_id', 'user_id', 'addtime'], 'required'],
            [['store_id', 'user_id', 'addtime', 'is_use', 'is_read'], 'integer'],
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
            'addtime' => 'Addtime',
            'is_use' => 'Is Use',
            'is_read' => 'Is Read',
        ];
    }
}
