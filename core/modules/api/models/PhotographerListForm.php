<?php
/**
 * Created by IntelliJ IDEA.
 * User: luwei
 * Date: 2017/7/1
 * Time: 23:33
 */

namespace app\modules\api\models;


use app\hejiang\ApiResponse;
use app\models\Cat;
use app\models\Goods;
use app\models\GoodsCat;
use app\models\GoodsPic;
use app\models\Label;
use app\models\Order;
use app\models\OrderDetail;
use app\models\Photographer;
use app\models\PhotographerLevel;
use yii\data\Pagination;

class PhotographerListForm extends Model
{
    public $store_id;
    public $keyword;
    public $level_id;

    public $limit = 10;
    public $page;
    public $u_lat;
    public $u_lon;
    public $EARTH_RADIUS = 6371;


    public function rules()
    {
        return [
            [['keyword'], 'trim'],
            [['store_id', 'page', 'limit',], 'integer'],
            [['limit'], 'integer',],
            [['limit',], 'default', 'value' => 12],

            [['u_lat', 'u_lon'], 'double'],
        ];
    }

    public function search()
    {



        if (!$this->validate())
            return $this->errorResponse;

        $range = 180 / pi() * 10000 / 6372.797; //里面的 1 就代表搜索 1km 之内，单位km
        $lngR = $range / cos($this->u_lat * pi() / 180.0);
        $maxLat = $this->u_lat + $range;
        $minLat = $this->u_lat - $range;
        $maxLng = $this->u_lon + $lngR;
        $minLng = $this->u_lon - $lngR;
//      'g.status' => 1,
        $query = Photographer::find()->alias('p')
            ->where([
                'p.store_id' => $this->store_id,
                'p.is_delete' => 0,
                'p.is_hide' => 0,
                'p.status'=>1
            ])->orderBy('m.sort,m.addtime DESC');
        if ($this->u_lat) {
            $query->andWhere(['between', 'p.lat', $minLat, $maxLat]);
        }
        if ($this->u_lon) {
            $query->andWhere(['between', 'p.lon', $minLng, $maxLng]);

        }

        if ($this->store_id)
            $query->andWhere(['p.store_id' => $this->store_id]);
        if ($this->level_id) {
            $query->andWhere(['>=','p.level_id',$this->level_id]);
        }
        if ($this->keyword)
            $query->andWhere(['LIKE', 'p.name', $this->keyword]);
        $count = $query->count();
        $pagination = new Pagination(['totalCount' => $count, 'page' => $this->page - 1, 'pageSize' => 10,]);

        $query = $query->limit($pagination->limit)->offset($pagination->offset)
            ->select('p.*')
            ->addSelect(['ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN((' . $this->u_lat . ' * PI() / 180 - p.lat * PI() / 180) / 2),2) + 
COS(' . $this->u_lat . ' * PI() / 180) * COS(p.lat * PI() / 180) * POW(SIN((' . $this->u_lon . ' * PI() / 180 - p.lon * PI() / 180) / 2),2)))
,2) AS distance'])
            ->orderBy('distance ASC');

        $sql = $query->createCommand()->rawSql;


        $list = $query->asArray()->all();
        foreach ($list as $i => $item) {
            $label_list = Label::find()->where(['user_id' => $item['user_id'], 'is_delete' => '0'])->asArray()->all();
            $item['labels'] = $label_list;
            $level = PhotographerLevel::findOne($item['level_id']);
            $item['level'] = $level->name;

            $list[$i] = $item;
        }
        $sum= Photographer::find()->where(['store_id'=>$this->store_id])->count();


        $data = [
            'row_count' => $count,
            'page_count' => $pagination->pageCount,
            'list' => $list,
            'sum'=>$sum
        ];
        return new ApiResponse(0, 'success', $data);
    }

    public function recommend()
    {
        if (!$this->validate())
            return $this->errorResponse;
        $goods_id = $this->goods_id;
        if (!$goods_id) {
            return new ApiResponse(1, 'error');
        }
        $cat_ids = [];

        $goods = Goods::find()->select('*')->where(['store_id' => $this->store_id, 'is_delete' => 0])->andWhere('id=:id', [':id' => $goods_id])->one();
        $cat_id = $goods->cat_id;

        if ($cat_id == 0) {
            $goodsCat = GoodsCat::find()->select('cat_id')->where(['store_id' => $this->store_id, 'goods_id' => $goods_id, 'is_delete' => 0])->all();
            $goods_cat = [];
            foreach ($goodsCat as $v) {
                $goods_cat[] = $v->cat_id;
            }
        } else {
            $goods_cat = array(intval($cat_id));
        }
        $cat_ids = $goods_cat;
        // $cat1 = Cat::find()->select(['id','parent_id'])->where(['store_id' =>$this->store_id,'is_delete' => 0])->andWhere(['in','id',$goods_cat])->all();
        // $parents=[];
        // foreach($cat1 as $v){
        //     if($v->parent_id===0){
        //         $cat_ids[] = $v->id;
        //     }else{
        //         $parents[] = $v->parent_id;
        //     }
        // };
        // $cat2 = Cat::find()->select('id')->where(['store_id' =>$this->store_id,'is_delete' => 0])->andWhere(['in','id',$parents])->all();
        // foreach($cat2 as $v){
        //     $cat_ids[] = $v->id;
        // }

        // $cat_list = Cat::find()->select('id')->where(['store_id'=>$this->store_id,'is_delete'=>0])->andWhere(['in','parent_id',$cat_ids])->all();
        // foreach($cat_list as $v){
        //     $cat_ids[] =$v->id;
        // }
        //查询
        $goodscat_list = GoodsCat::find()->select(['goods_id'])->where(['store_id' => $this->store_id, 'is_delete' => 0])->andWhere(['in', 'cat_id', $cat_ids])->all();

        $cats = [];
        foreach ($goodscat_list as $v) {
            $cats[] = $v->goods_id;
        }

        $query = Goods::find()->alias('g')
            ->where(['and', "g.id!=$goods_id", 'cat_id=0', "store_id=$this->store_id", 'is_delete=0', 'status=1', ['in', 'id', $cats]])
            ->orWhere(['and', "g.id!=$goods_id", "store_id=$this->store_id", 'is_delete=0', 'status=1', ['in', 'cat_id', $cat_ids]]);

        $count = $query->count();
        $pagination = new Pagination(['totalCount' => $count, 'pageSize' => $this->limit, 'page' => $this->page - 1]);

        $query->orderBy('g.sort ASC');

        $od_query = OrderDetail::find()->alias('od')
            ->leftJoin(['o' => Order::tableName()], 'od.order_id=o.id')
            ->where(['od.is_delete' => 0, 'o.store_id' => $this->store_id, 'o.is_pay' => 1, 'o.is_delete' => 0])->groupBy('od.goods_id')->select('SUM(od.num) num,od.goods_id');


        $limit = $pagination->limit;
        $offset = $pagination->offset;
        $recommend_count = $this->recommend_count;
        if ($offset > $recommend_count) {
            return new ApiResponse(1, 'error');
        } else if ($offset + $limit > $recommend_count) {
            $limit = $recommend_count - $offset;
        }

        $list = $query
            ->leftJoin(['gn' => $od_query], 'gn.goods_id=g.id')
            ->select('g.id,g.name,g.price,g.original_price,g.cover_pic pic_url,gn.num,g.virtual_sales,g.unit')
            ->limit($limit)
            ->offset($pagination->offset)
            ->asArray()->groupBy('g.id')->all();

        foreach ($list as $i => $item) {
            if (!$item['pic_url']) {
                $list[$i]['pic_url'] = Goods::getGoodsPicStatic($item['id'])->pic_url;
            }
            $list[$i]['sales'] = $this->numToW($item['num'] + $item['virtual_sales']) . $item['unit'];

        }
        $data = [
            'row_count' => $count,
            'page_count' => $pagination->pageCount,
            'list' => $list,
        ];
        return new ApiResponse(0, 'success', $data);
    }

    private function numToW($sales)
    {
        if ($sales < 10000) {
            return $sales;
        } else {
            return round($sales / 10000, 2) . 'W';
        }
    }

    public function couponSearch()
    {
//        ,'name','price','original_price','pic_url','num','virtual_sales','unit'
        $arr = explode(",", $this->goods_id);

        $query = Goods::find()->where(['store_id' => $this->store_id, 'is_delete' => 0, 'status' => 1])->andWhere(['in', 'id', $arr]);
        $count = $query->count();
        $pagination = new Pagination(['totalCount' => $count, 'pageSize' => $this->limit, 'page' => $this->page - 1]);

        if ($this->sort == 0) {
            //综合，自定义排序+时间最新
            $query->orderBy('sort ASC,addtime DESC');
        }
        if ($this->sort == 1) {
            //时间最新
            $query->orderBy('addtime DESC');
        }
        if ($this->sort == 2) {
            //价格
            if ($this->sort_type == 0) {
                $query->orderBy('price ASC');
            } else {
                $query->orderBy('price DESC');
            }
        }
        if ($this->sort == 3) {
            //销量
            $query->orderBy([
                'virtual_sales' => SORT_DESC,
                'addtime' => SORT_DESC,
            ]);
        }
        $list = $query
            ->select(['id', 'name', 'cover_pic as pic_url', 'price', 'original_price', 'virtual_sales as sales', 'unit'])
            ->limit($pagination->limit)
            ->offset($pagination->offset)
            ->asArray()->all();
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

}