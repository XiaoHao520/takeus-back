<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%photographer}}".
 *
 * @property integer $id
 * @property integer $store_id
 * @property string $name
 * @property string $user_id
 * @property integer $level_id
 * @property integer $is_delete
 * @property integer $addtime
 * @property double $lat
 * @property double $lon
 * @property string $header_url
 * @property string $address
 * @property string $details
 * @property integer $status
 * @property integer $sex
 * @property integer $is_hide
 * @property  integer $id_card
 * @property string $mobile
 * @property integer $accept
 */
class  Photographer extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%photographer}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['store_id', 'name','header_url','level_id','user_id'], 'required'],
            [['store_id' , 'addtime', 'is_delete','status','sex','is_hide','accept'], 'integer'],
            [['header_url','id_card','mobile'], 'string'],
            [['lat','lon'],'double'],
            [['name','header_url','address','details','id_card'], 'string', 'max' => 255],
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
            'name' => '摄影师名字',
            'header_url' => '头像',
            'level_id'=>"等级ID",
            'user_id'=>'用户id',
            'addtime' => 'Addtime',
            'is_delete' => 'Is Delete',
            'lat'=>'纬度',
            'lon'=>'经度',
            'address'=>'位置',
            'details'=>'介绍',
            'status'=>'状态',
            'sex'=>'性别',
            'is_hide'=>'隐藏位置',
            'id_card'=>'身份证',
            'mobile'=>'手机号',
            'accept'=>'接受低级单'
        ];
    }

    /**
     * @return array
     */
    public function savePhotographer()
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
