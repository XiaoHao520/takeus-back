<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%requirement}}".
 *
 * @property integer $id
 * @property integer $store_id
 * @property string $name
 * @property string $pic_url
 * @property integer $addtime
 * @property integer $is_delete
 */
class Requirement extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%requirement}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['store_id', 'name',], 'required'],
            [['store_id' , 'addtime', 'is_delete',], 'integer'],
            [['pic_url'], 'string'],
            [['name'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'store_id' => '商城id',

            'name' => '分类名称',
            'pic_url' => '分类图片url',

            'addtime' => 'Addtime',
            'is_delete' => 'Is Delete',

        ];
    }

    /**
     * @return array
     */
    public function saveRequirement()
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
