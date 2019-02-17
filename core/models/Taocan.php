<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%taocan}}".
 *
 * @property integer $id
 * @property integer $store_id
 * @property integer $level_id
 * @property integer $requirement_id
 * @property string $price
 * @property integer $addtime
 * @property integer $is_delete
 * @property string $name
 * @property integer $number
 * @property  integer $minutes
 */
class Taocan extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%taocan}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['store_id', 'level_id', 'requirement_id', 'price', 'addtime', 'name'], 'required'],
            [['store_id', 'level_id', 'requirement_id', 'addtime', 'is_delete','minutes','number'], 'integer'],
            [['name'], 'string'],
            [['price'], 'number'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'store_id' => 'store_id',
            'level_id' => '摄影师等级ID',
            'requirement_id' => '需求类型ID',
            'price' => '价格',
            'addtime' => 'addtime',
            'is_delete' => 'is_delete',
            'name' => 'name',
            'minutes'=>'套餐时长',
            'number'=>'照片张数'
        ];
    }

    public function saveTaocan()
    {
        if ($this->validate()) {
            if ($this->save(false)) {
                return [
                    'code' => 0,
                    'msg' => '成功'
                ];
            } else {
                return [
                    'code' => 1,
                    'msg' => '失败'
                ];
            }
        } else {
            return (new Model())->getErrorResponse($this);
        }
    }
}
