<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%product_list}}".
 *
 * @property integer $id
 * @property integer $store_id
 * @property integer $user_id
 * @property string $pic_url
 * @property integer $is_delete
 * @property integer $addtime
 */
class ProductList extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%product_list}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['store_id', 'user_id', 'pic_url', 'addtime'], 'required'],
            [['store_id', 'user_id', 'is_delete', 'addtime'], 'integer'],
            [['pic_url'], 'string', 'max' => 255],
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
            'pic_url' => 'Pic Url',
            'is_delete' => 'Is Delete',
            'addtime' => 'Addtime',
        ];
    }
}