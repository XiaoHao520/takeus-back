<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%online}}".
 *
 * @property integer $id
 * @property integer $store_id
 * @property integer $user_id
 * @property integer $start
 * @property integer $end
 * @property integer $is_pay
 * @property integer $addtime
 * @property string $total
 */
class Online extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%online}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['store_id', 'user_id', 'addtime'], 'required'],
            [['store_id', 'user_id', 'start', 'end', 'is_pay', 'addtime'], 'integer'],
            [['total'], 'number'],
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
            'is_pay' => 'Is Pay',
            'addtime' => 'Addtime',
            'total' => 'Total',
        ];
    }
}
