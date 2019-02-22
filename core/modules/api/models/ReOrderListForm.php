<?php
/**
 * Created by IntelliJ IDEA.
 * User: luwei
 * Date: 2017/7/18
 * Time: 19:13
 */

namespace app\modules\api\models;


use app\models\Goods;
use app\models\Mch;
use app\models\Option;
use app\models\Order;
use app\models\OrderDetail;
use app\models\OrderPic;
use app\models\OrderRefund;
use app\models\Photographer;
use app\models\PhotographerLevel;
use app\models\Requirement;
use app\models\RequirementOrder;
use app\models\Reward;
use app\models\Taocan;
use app\models\UserTplMsgSender;
use yii\data\Pagination;
use yii\helpers\VarDumper;

class ReOrderListForm extends Model
{
    public $store_id;
    public $user_id;
    public $status;
    public $page;
    public $limit;
    public $photographer_id;

    public function rules()
    {
        return [
            [['page', 'limit', 'status',], 'integer'],
            [['page',], 'default', 'value' => 1],
            [['limit',], 'default', 'value' => 20],
        ];
    }

    public function search()
    {
        if (!$this->validate())
            return $this->errorResponse;
        $query = RequirementOrder::find()->alias('ro')->where([
            'ro.is_delete' => 0,
            'ro.store_id' => $this->store_id,


        ]);




        if ($this->photographer_id) {
            $query->andWhere(['ro.photographer_id' => $this->photographer_id]);

        } else {
            $query->andWhere(['ro.user_id' => $this->user_id]);
        }

        $query->leftJoin(Taocan::tableName().'t','t.id=ro.taocan_id')
            ->leftJoin(Photographer::tableName() . 'p', 'p.id=ro.photographer_id')
            ->leftJoin(PhotographerLevel::tableName() . 'pl', 'pl.id=p.level_id')
            ->leftJoin(Requirement::tableName() . 'r', 'r.id=ro.requirement_id');

        if ($this->status == 0) {//待付款


            $query->andWhere([
                'ro.is_pay' => 0,
            ]);
        }
        if ($this->status == 1) {//待接单
            $query->andWhere([
                'ro.is_pay' => 1,
                'ro.status' => 1
            ]);
        }
        if ($this->status == 2) {//服务中  已接单
            $query->andWhere([
                'ro.is_pay' => 1,
                'ro.status' => 2
            ]);
        }
        if ($this->status == 3) {//可下载
            $query->andWhere([
                'ro.is_pay' => 1,
                'ro.status' => 3
            ]);
        }
        if ($this->status == 4) {//已完成
            $query->andWhere([
                'ro.is_pay' => 1,
                'ro.status' => 4
            ]);
        }
        /* if ($this->status == 4) {//售后订单
             return $this->getRefundList();
         }*/
        $query->select('ro.*,r.name as r_name,pl.name as l_name,p.name as p_name,p.header_url as pic_url,t.name as t_name,t.minutes,t.number,p.mobile as p_mobile');
        $count = $query->count();
        $pagination = new Pagination(['totalCount' => $count, 'page' => $this->page - 1, 'pageSize' => $this->limit]);
        /* @var Order[] $list */
        $list = $query->limit($pagination->limit)->offset($pagination->offset)->orderBy('ro.addtime DESC')->asArray()->all();

        foreach ($list as $i => $order) {
            $order['addtime'] = date('Y-m-d H:i:s', $order['addtime']);
            $query_2 = Reward::find()->where(['order_id' => $order['id'], 'is_delete' => 0]);
            $query_3 = clone $query_2;
            $sum_price = $query_3->andWhere(['is_pay' => 1])->asArray()->sum('price');
            $reward = null;
            $reward = $query_2->andWhere(['is_pay' => 0])->orderBy('addtime DESC')->one();
            if ($reward) {
                $order['add_status'] = $reward['is_pay'];
                $order['add_price'] = $reward['price'];
                $order['reward_id'] = $reward['id'];
            } else {
                $order['add_price'] = 0;
                $order['add_status'] = 1;
            }


            $order['price'] = $order['price'] + $order['add_price'] + $sum_price;


            $order_pic=OrderPic::findOne(['order_id'=>$order['id']]);
            if($order_pic){
                $order['is_upload']=1;
            }else{
                $order['is_upload']=0;
            }
            $list[$i] = $order;
        }

        return [
            'code' => 0,
            'msg' => 'success',
            'data' => [
                'row_count' => $count,
                'page_count' => $pagination->pageCount,
                'list' => $list,

            ],
        ];

    }

    private function getRefundList()
    {
        $query = OrderRefund::find()->alias('or')
            ->leftJoin(['od' => OrderDetail::tableName()], 'od.id=or.order_detail_id')
            ->leftJoin(['o' => Order::tableName()], 'o.id=or.order_id')
            ->where([
                'or.store_id' => $this->store_id,
                'or.user_id' => $this->user_id,
                'or.is_delete' => 0,
                'o.is_delete' => 0,
                'od.is_delete' => 0,
            ]);
        $count = $query->count();
        $pagination = new Pagination(['totalCount' => $count, 'page' => $this->page - 1, 'pageSize' => $this->limit]);
        $list = $query->select('o.id AS order_id,o.order_no,or.id AS order_refund_id,od.goods_id,or.addtime,od.num,od.total_price,od.attr,or.refund_price,or.type,or.status')->limit($pagination->limit)->offset($pagination->offset)->orderBy('or.addtime DESC')->asArray()->all();
        $new_list = [];
        foreach ($list as $item) {
            $goods = Goods::findOne($item['goods_id']);
            if (!$goods)
                continue;
            $new_list[] = (object)[
                'order_id' => intval($item['order_id']),
                'order_no' => $item['order_no'],
                'goods_list' => [(object)[
                    'goods_id' => intval($goods->id),
                    'goods_pic' => $goods->getGoodsPic(0)->pic_url,
                    'goods_name' => $goods->name,
                    'num' => intval($item['num']),
                    'price' => doubleval(sprintf('%.2f', $item['total_price'])),
                    'attr_list' => json_decode($item['attr']),
                ]],
                'addtime' => date('Y-m-d H:i', $item['addtime']),
                'refund_price' => doubleval(sprintf('%.2f', $item['refund_price'])),
                'refund_type' => $item['type'],
                'refund_status' => $item['status'],
                'order_refund_id' => $item['order_refund_id'],
            ];
        }
        return [
            'code' => 0,
            'msg' => 'success',
            'data' => [
                'row_count' => $count,
                'page_count' => $pagination->pageCount,
                'list' => $new_list,
            ],
        ];
    }

    public static function getCountData($store_id, $user_id)
    {
        $form = new OrderListForm();
        $form->limit = 1;
        $form->store_id = $store_id;
        $form->user_id = $user_id;
        $data = [];

        $form->status = -1;
        $res = $form->search();

        $data['all'] = $res['data']['row_count'];

        $form->status = 0;
        $res = $form->search();
        $data['status_0'] = $res['data']['row_count'];

        $form->status = 1;
        $res = $form->search();
        $data['status_1'] = $res['data']['row_count'];

        $form->status = 2;
        $res = $form->search();
        $data['status_2'] = $res['data']['row_count'];

        $form->status = 3;
        $res = $form->search();
        $data['status_3'] = $res['data']['row_count'];

        return $data;
    }

}