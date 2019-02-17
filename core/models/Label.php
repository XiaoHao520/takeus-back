<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%label}}".
 *
 * @property integer $id
 * @property integer $store_id
 * @property integer $user_id
 * @property string $content
 * @property integer $is_delete
 * @property integer $addtime
 */
class Label extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%label}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['store_id', 'user_id', 'content', 'addtime'], 'required'],
            [['store_id', 'user_id', 'is_delete', 'addtime'], 'integer'],
            [['content'], 'string', 'max' => 10],
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
            'content' => 'Content',
            'is_delete' => 'Is Delete',
            'addtime' => 'Addtime',
        ];
    }
}
