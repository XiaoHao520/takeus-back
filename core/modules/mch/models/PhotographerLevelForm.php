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

class PhotographerLevelForm extends Model
{
    public $level;

    public $store_id;

    public $name;
    public $pic_url;
    public $weight;







    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'store_id'], 'required'],
            [['store_id','weight'], 'integer'],
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
            'name' => '分类名称',
            'pic_url' => '分类图片url',
            'weight'=>'权重'
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
            $level = $this->level;
            if ($level->isNewRecord) {
                $level->is_delete = 0;
                $level->addtime = time();
            }
            $level->attributes = $this->attributes;
            return $level->saveLevel();
        } else {
            return $this->errorResponse;
        }
    }

}