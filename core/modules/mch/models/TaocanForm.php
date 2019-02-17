<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/6/27
 * Time: 11:01
 */

namespace app\modules\mch\models;

use app\models\Cat;
use app\models\Model;
use yii\data\Pagination;

class TaocanForm extends Model
{
    public $taocan;
    public $store_id;
    public $level_id;
    public $price;
    public $requirement_id;
    public $name;
    public  $minutes;
    public $number;






    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['level_id', 'store_id','requirement_id'], 'required'],
            [['level_id', 'store_id','requirement_id','minutes','number'], 'integer'],
            [['price','name'], 'string'],

        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [

            'store_id' => 'store_id',
            'level_id' => '摄影师等级ID',
            'requirement_id' => '需求类型ID',
            'price' => '价格',
            'addtime' => 'addtime',
            'is_delete' => 'is_delete',
            'name'=>'name',
            'number'=>'number',
            'minutes'=>'minutes'

        ];
    }

    /**
     * @param $store_id
     * @return array
     * 获取列表数据
     */
    public function getList($store_id)
    {
        $query = Cat::find()->andWhere(['is_delete' => 0, 'store_id' => $store_id]);
        $count = $query->count();
        $p = new Pagination(['totalCount' => $count, 'pageSize' => 20]);
        $list = $query
            ->orderBy('sort ASC')
            ->offset($p->offset)
            ->limit($p->limit)
            ->asArray()
            ->all();

        return [$list, $p];
    }

    /**
     * 编辑
     * @return array
     */
    public function save()
    {
        if ($this->validate()) {
            $taocan = $this->taocan;
            if ($taocan->isNewRecord) {
                $taocan->is_delete = 0;
                $taocan->addtime = time();
            }
            $taocan->attributes = $this->attributes;
            return $taocan->saveTaocan();
        } else {
            return $this->errorResponse;
        }
    }

}