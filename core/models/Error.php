<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%error}}".
 *
 * @property integer $id
 * @property integer $store_id
 * @property string $details
 */
class Error extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%error}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['store_id', 'details'], 'required'],
            [['store_id'], 'integer'],
            [['details'], 'string', 'max' => 512],
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
            'details' => 'Details',
        ];
    }
}
