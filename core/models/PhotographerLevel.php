<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%photographer_level}}".
 *
 * @property integer $id
 * @property integer $store_id
 * @property string $name
 * @property string $pic_url
 * @property integer $addtime
 * @property integer $is_delete
 * @property integer $weight
 */
class  PhotographerLevel extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%photographer_level}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['store_id', 'name',], 'required'],
            [['store_id' , 'addtime', 'is_delete','weight'], 'integer'],
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

            'name' => '级别名称',
            'pic_url' => '级别图片url',

            'addtime' => 'Addtime',
            'is_delete' => 'Is Delete',

            'weight'=>'权重'
        ];
    }

    /**
     * @return array
     */
    public function saveLevel()
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

    public function getParent()
    {
        return $this->hasOne(Cat::className(), ['id' => 'parent_id']);
    }

    public function getChildrenList()
    {
        return $this->hasMany(Cat::className(), ['parent_id' => 'id'])->where(['is_delete' => 0])->orderBy('sort,addtime DESC');
    }

    public function getGoodsCat()
    {
        return $this->hasMany(GoodsCat::className(),['cat_id'=>'id']);
    }

}
