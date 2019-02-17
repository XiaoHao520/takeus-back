<?php
/**
 * Created by IntelliJ IDEA.
 * User: luwei
 * Date: 2017/7/20
 * Time: 14:34
 */

namespace app\modules\mch\models;


use app\models\Goods;
use app\models\Order;
use app\models\OrderDetail;
use app\models\OrderRefund;
use app\models\Photographer;
use app\models\PhotographerLevel;
use app\models\Recharge;
use app\models\ReOrder;
use app\models\Requirement;
use app\models\RequirementOrder;
use app\models\Share;
use app\models\Shop;
use app\models\User;
use app\modules\mch\extensions\Export;
use yii\data\Pagination;
use yii\helpers\ArrayHelper;
use app\models\GoodsPic;

class SyOrderListForm extends Model
{
    public $store_id;
    public $user_id;
    public $keyword;
    public $status;
    public $is_recycle;
    public $page;
    public $limit;

    public $flag;//是否导出
    public $is_offline;
    public $clerk_id;
    public $parent_id;
    public $shop_id;

    public $date_start;
    public $date_end;

    public $keyword_1;
    public $seller_comments;

    public function rules()
    {
        return [
            [['keyword',], 'trim'],
            [['status', 'is_recycle', 'page', 'limit', 'user_id', 'is_offline', 'clerk_id', 'shop_id', 'keyword_1'], 'integer'],
            [['status',], 'default', 'value' => -1],
            [['page',], 'default', 'value' => 1],
            //[['limit',], 'default', 'value' => 20],
            [['flag', 'date_start', 'date_end'], 'trim'],
            [['flag'], 'default', 'value' => 'no'],
            [['seller_comments'], 'string'],
        ];
    }

    public function search()
    {
        if (!$this->validate())
            return $this->errorResponse;
        $query = RequirementOrder::find()->alias('o')->where([
            'o.store_id' => $this->store_id,

        ])->leftJoin(['u' => User::tableName()], 'u.id = o.user_id');

        switch ($this->status) {
            case 0:
                $query->andWhere(['o.is_pay' => 0]);
                break;
            case 1:
                $query->andWhere([
                    'o.status' => 1,
                ])->andWhere(['o.is_pay' => 1]);
                break;
            case 2:
                $query->andWhere([
                    'o.status' => 2,

                ])->andWhere(['o.is_pay' => 1]);
                break;
            case 3:
                $query->andWhere([
                    'o.status' => 3,

                ])->andWhere(['o.is_pay' => 1]);
                break;
            case 4:
                $query->andWhere([
                    'o.status' => 4,
                ])->andWhere(['o.is_pay' => 1]);
                break;
            case 5:
                break;
            case 6:
                $query->andWhere(['o.apply_delete' => 1]);
                break;
            default:
                break;
        }
        if ($this->status == 5) {//已取消订单
            $query->andWhere(['or', ['o.is_cancel' => 1], ['o.is_delete' => 1]]);
        } else {
            if ($this->is_recycle != 1) {
                $query->andWhere(['o.is_cancel' => 0, 'o.is_delete' => 0]);
            }
        }

        if ($this->user_id) {//查找指定用户的
            $query->andWhere([
                'o.user_id' => $this->user_id,
            ]);
        }

        if ($this->date_start) {
            $query->andWhere(['>=', 'o.addtime', strtotime($this->date_start)]);
        }
        if ($this->date_end) {
            $query->andWhere(['<=', 'o.addtime', strtotime($this->date_end) + 86400]);
        }

        if ($this->keyword) {//关键字查找
            switch ($this->keyword_1) {
                case 1:
                    $query->andWhere(['like', 'o.order_no', $this->keyword]);
                    break;
                case 2:
                    $query->andWhere(['like', 'u.nickname', $this->keyword]);
                    break;
                case 3:
                    $query->andWhere(['like', 'o.name', $this->keyword]);
                    break;
                default:
                    break;
            }
        }


        if ($this->flag == "EXPORT") {
            $query_ex = clone $query;
            $list_ex = $query_ex->select('o.*,u.nickname')->orderBy('o.addtime DESC')->asArray()->all();
            foreach ($list_ex as $i => &$item) {
                $item['goods_list'] = $this->getOrderGoodsList($item['id']);
            }
            Export::order_2($list_ex, $this->is_offline);
        }
        $count = $query->count();
        $pagination = new Pagination(['totalCount' => $count, 'pageSize' => $this->limit, 'page' => $this->page - 1]);

          $query->leftJoin(Photographer::tableName().'p','p.id=o.photographer_id')
              ->leftJoin(PhotographerLevel::tableName().'pl','pl.id=p.level_id')
              ->leftJoin(Requirement::tableName().'r','r.id=o.requirement_id');



        $list = $query->limit($pagination->limit)->offset($pagination->offset)->orderBy('o.addtime DESC')
            ->select(['o.*', 'u.nickname','p.name as p_name','p.header_url as goods_pic','pl.name as level_name','r.name as r_name'])->asArray()->all();

        $listArray = ArrayHelper::toArray($list);


        return [
            'row_count' => $count,
            'page_count' => $pagination->pageCount,
            'pagination' => $pagination,
            'list' => $listArray,
        ];

    }

    public function getOrderGoodsList($order_id)
    {
        $picQuery = GoodsPic::find()
            ->alias('gp')
            ->select('pic_url')
            ->andWhere('gp.goods_id = od.goods_id')
            ->andWhere(['is_delete' => 0])
            ->limit(1);
        $orderDetailList = OrderDetail::find()->alias('od')
            ->leftJoin(['g' => Goods::tableName()], 'od.goods_id=g.id')
            ->where([
                'od.is_delete' => 0,
                'od.order_id' => $order_id,
            ])->select(['od.*', 'g.name', 'g.unit', 'goods_pic' => $picQuery])->asArray()->all();
        foreach ($orderDetailList as $i => &$item) {
            //$goods = new Goods();
            //$goods->id = $item['goods_id'];
            //$item['goods_pic'] = $goods->getGoodsPic(0)->pic_url;
            $item['attr_list'] = json_decode($item['attr']);
        }
        return $orderDetailList;
    }

    public static function getCountData($store_id)
    {
        $form = new OrderListForm();
        $form->limit = 0;
        $form->store_id = $store_id;
        $data = [];

        $form->status = -1;
        $res = $form->search();
        $data['all'] = $res['row_count'];

        $form->status = 0;
        $res = $form->search();
        $data['status_0'] = $res['row_count'];

        $form->status = 1;
        $res = $form->search();
        $data['status_1'] = $res['row_count'];

        $form->status = 2;
        $res = $form->search();
        $data['status_2'] = $res['row_count'];

        $form->status = 3;
        $res = $form->search();
        $data['status_3'] = $res['row_count'];

        $form->status = 5;
        $res = $form->search();
        $data['status_5'] = $res['row_count'];

        return $data;
    }
}