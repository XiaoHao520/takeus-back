<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%photographer_online}}".
 *
 * @property integer $id
 * @property integer $store_id
 * @property integer $user_id
 * @property integer $start
 * @property integer $end
 * @property integer $total
 * @property integer $is_pay
 * @property integer $addtime
 */
class PhotographerOnline extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%photographer_online}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['store_id', 'user_id'], 'required'],
            [['store_id', 'user_id', 'start', 'end', 'total', 'is_pay', 'addtime'], 'integer'],
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
            'start' => 'Start',
            'end' => 'End',
            'total' => 'Total',
            'is_pay' => 'Is Pay',
            'addtime' => 'Addtime',
        ];
    }
}
