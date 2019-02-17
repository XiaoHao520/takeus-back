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
use app\models\Order;
use app\models\OrderDetail;
use app\models\Photographer;
use app\models\RequirementOrder;
use yii\data\Pagination;

class AreaReOrderListForm extends Model
{
    public $store_id;

    public $limit=10;
    public $page;
    public $u_lat;
    public $u_lon;
    public $EARTH_RADIUS = 6371;


    public function rules()
    {
        return [

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


        $sub_time=time()-24*60*60;

        $range = 180 / pi() * 10000 / 6372.797; //里面的 1 就代表搜索 1km 之内，单位km
        $lngR = $range / cos($this->u_lat * pi() / 180.0);
        $maxLat = $this->u_lat + $range;
        $minLat = $this->u_lat - $range;
        $maxLng = $this->u_lon + $lngR;
        $minLng = $this->u_lon - $lngR;
//      'g.status' => 1,
        $query = RequirementOrder::find()
            ->where([
                'store_id' => $this->store_id,
                'is_delete' => 0,
                'is_confirm'=>1,
            ]);
         $query->andWhere(['<','confirm_time',$sub_time]);

        if ($this->u_lat) {
            $query->andWhere(['between', 'lat', $minLat, $maxLat]);
        }
        if ($this->u_lon) {
            $query->andWhere(['between', 'lon', $minLng, $maxLng]);

        }

        if ($this->store_id)
            $query->andWhere(['store_id' => $this->store_id]);

        $count = $query->count();


        $query = $query->select('*')
            ->addSelect(['ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN((' . $this->u_lat . ' * PI() / 180 - lat * PI() / 180) / 2),2) + 
COS(' . $this->u_lat . ' * PI() / 180) * COS(lat * PI() / 180) * POW(SIN((' . $this->u_lon . ' * PI() / 180 - lon * PI() / 180) / 2),2)))
,2) AS distance'])->limit(10)->orderBy('distance ASC');

        $sql = $query->createCommand()->rawSql;

        $list = $query->asArray()->all();
        $data = [
            'row_count' => $count,
            'list' => $list,
            'sql' => $sql
        ];
        return new ApiResponse(0, 'success', $data);
    }




}