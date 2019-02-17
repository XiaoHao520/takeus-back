<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/11
 * Time: 10:45
 */

namespace app\modules\mch\models;


use app\models\BalanceCash;
use app\models\Cash;
use yii\data\Pagination;

class BalanceCashForm extends Model
{
    public $store_id;
    public $user_id;
    public $page;
    public $limit;
    public $status;
    public $keyword;
    public $id;

    public function rules()
    {
        return [
            [['keyword',], 'trim'],
            [['page','limit','status','id'],'integer'],
            [['status',], 'default', 'value' => -1],
            [['page'],'default','value'=>1]
        ];
    }



    public function getList()
    {
        $query = BalanceCash::find()->alias('c')
            ->where(['c.is_delete'=>0,'c.store_id'=>$this->store_id])
            ->leftJoin('{{%user}} u','u.id=c.user_id');
        if($this->keyword){
            $query->andWhere(['like','u.nickname',$this->keyword]);
        }
        if($this->status == 0 and $this->status != ''){//待审核
            $query->andWhere(['c.status'=>0]);
        }
        if($this->status == 1){//待打款
            $query->andWhere(['c.status'=>1]);
        }
        if($this->status == 2){//已打款
            $query->andWhere(['in','c.status',[2,5]]);
        }

        if($this->status == 3){//无效
            $query->andWhere(['c.status'=>3]);
        }
        if($this->id){
            $query->andWhere(['s.id'=>$this->id]);
        }
        $count = $query->count();
        $pagination = new Pagination(['totalCount' => $count, 'pageSize' => $this->limit, 'page' => $this->page - 1]);
        $list = $query->limit($pagination->limit)->offset($pagination->offset)->orderBy('c.status ASC,c.addtime DESC')
            ->select([
                'c.*','u.nickname','u.avatar_url','u.id user_id'
            ])->asArray()->all();

        return [$list,$pagination];
    }

    public function getCount()
    {
        $list = BalanceCash::find()->select([
            'sum(case when status = 0 then 1 else 0 end) count_1',
            'sum(case when status = 1 then 1 else 0 end) count_2',
            'sum(case when status = 2 or status = 5 then 1 else 0 end) count_3',
            'sum(case when status = 3 then 1 else 0 end) count_4',
            'count(1) total'
        ])->where(['is_delete'=>0,'store_id'=>$this->store_id])->asArray()->one();
        return $list;
    }
}