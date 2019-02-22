<?php
/**
 * Created by PhpStorm.
 * User: zc
 * Date: 2018/4/25
 * Time: 9:36
 */

namespace app\modules\api\models;

use app\models\Online;
use app\models\RequirementOrder;
use app\models\User;
use Curl\Curl;
class PhotographerForm extends Model
{
    public $store_id;

    public $photographer;
    public $user_id;



    public function search()
    {
        return [
            'code' => 0,
            'data' => [
                'data1' => $this->getPaySum(strtotime(date('Y-m-d 00:00:00')), strtotime(date('Y-m-d 23:59:59'))),
                'data2' => $this->getPayOrderCount(strtotime(date('Y-m-d 00:00:00')), strtotime(date('Y-m-d 23:59:59'))),
                'data3' => $this->getAllOrder(strtotime(date('Y-m-d 00:00:00')), strtotime(date('Y-m-d 23:59:59'))),
                'user'=>$this->getUserInfo(),
                'online'=>$this->yestodayOnline()
            ],
        ];
    }



    private  function  yestodayOnline(){

        $yesterday = strtotime(date("Y-m-d 00:00:00", strtotime("-1 day")));


        $query = Online::find()->where(['store_id' => $this->store_id,'user_id'=>$this->user_id]);
        if (is_int($yesterday)) {
            $query->andWhere(['>', 'addtime', $yesterday]);
        }
        $yes_end = $yesterday + 23 * 60 * 60 + 59 * 60+59;
        if (is_int($yes_end)) {
            $query->andWhere(['<', 'addtime', $yes_end]);
        }
        $online = $query->one();

        if($online){
            return $online;

        }else{
            return 0;
        }









    }


    private function getUserInfo(){

         $user=User::findOne($this->user_id);
         if($user){
             return $user;

         }else{


             return null;

         }


    }


    //获取付款金额
    public function getPaySum($start_time = null, $end_time = null)
    {
        $query = RequirementOrder::find()->where([
            'photographer_id' => $this->photographer->id,
            'is_pay' => 1,

        ]);
        if (is_int($start_time)) {
            $query->andWhere(['>=', 'addtime', $start_time]);
        }
        if (is_int($end_time)) {
            $query->andWhere(['<=', 'addtime', $end_time]);
        }

        $count = $query->sum('pay_price');
        return number_format($count ? $count : 0, 2, '.', '');
    }

    //付款订单数
    public function getPayOrderCount($start_time = null, $end_time = null, $format = true)
    {
        $query = RequirementOrder::find()->where([
            'photographer_id' => $this->photographer->id,
            'is_pay' => 1,

        ]);
        if (is_int($start_time)) {
            $query->andWhere(['>=', 'addtime', $start_time]);
        }
        if (is_int($end_time)) {
            $query->andWhere(['<=', 'addtime', $end_time]);
        }
        $count = $query->count('1');
        $count = $count ? $count : 0;
        if ($count >= 10000)
            $count = sprintf('%.2f', $count) . '万';
        return $count;
    }

    //总订单数
    public function getAllOrder($start_time = null, $end_time = null, $format = true)
    {
        $query = RequirementOrder::find()->where([
            'photographer_id' => $this->photographer->id,
        ]);
        if (is_int($start_time)) {
            $query->andWhere(['>=', 'addtime', $start_time]);
        }
        if (is_int($end_time)) {
            $query->andWhere(['<=', 'addtime', $end_time]);
        }
        $count = $query->count('1');
        $count = $count ? $count : 0;
        if ($count >= 10000)
            $count = sprintf('%.2f', $count) . '万';
        return $count;
    }

}