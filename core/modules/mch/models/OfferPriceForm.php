<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/6/8
 * Time: 11:06
 */

namespace app\modules\mch\models;


use app\models\District;
use app\models\Option;

class OfferPriceForm extends Model
{
    public $store_id;
    public $is_enable;
    public $total_price;
    public $rule_list;

    public function rules()
    {
        return [
            [['is_enable'], 'integer'],
            [['total_price'], 'number'],
            [['total_price'], 'default', 'value' => 0],
            [['rule_list'], 'safe']
        ];
    }

    public function save()
    {
        if (!$this->validate()) {
            return $this->errorResponse;
        }
        $model = [
            'is_enable' => $this->is_enable,
            'total_price' => $this->total_price,
            'rule_list' => $this->rule_list,
        ];
        Option::set('offer-price', $model, $this->store_id, 'admin');
        return [
            'code' => 0,
            'msg' => "保存成功"
        ];

    }

    public function search()
    {
        $model = Option::get('offer-price', $this->store_id, 'admin');
        $province = District::findAll(['level' => 'province']);
        $newList = [];
        foreach ($province as $key => $value) {
            $cityList = $value->city;
            $newItem = [];
            foreach ($cityList as $item) {
                $newItem[] = [
                    'id' => $item->id,
                    'name' => $item->name,
                    'show' => false
                ];
            }
            $newList[] = [
                'id' => $value->id,
                'name' => $value->name,
                'city' => $newItem,
                'show' => false
            ];
        }
        if (!$model) {
            $isEnable = 0;
            $totalPrice = 0;
            $ruleList = [];
        } else {
            $isEnable = $model->is_enable;
            $totalPrice = $model->total_price;
            $ruleList = $model->rule_list ? $model->rule_list : [];
        }

        return [
            'newList' => $newList,
            'ruleList' => $ruleList,
            'is_enable' => $isEnable,
            'total_price' => $totalPrice
        ];
    }
}