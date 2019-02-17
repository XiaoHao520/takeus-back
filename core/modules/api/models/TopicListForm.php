<?php
/**
 * Created by IntelliJ IDEA.
 * User: luwei
 * Date: 2017/9/28
 * Time: 14:11
 */

namespace app\modules\api\models;


use app\hejiang\ApiResponse;
use app\models\Photographer;
use app\models\Topic;
use app\models\User;
use yii\data\Pagination;
use app\models\TopicType;

class TopicListForm extends Model
{
    public $store_id;

    public $page;
    public $limit = 20;
    public $type;
    public $is_chosen;
    public $user_id;

    public function rules()
    {
        return [
            [['page'], 'integer'],
            [['page'], 'default', 'value' => 1],
            ['type', 'integer'],
            ['is_chosen', 'integer'],
        ];
    }

    public function search()
    {


        if ($this->type === '-1') {
            $query = Topic::find()->where(['store_id' => $this->store_id, 'is_delete' => 0, 'is_chosen' => 1]);
        } elseif ($this->type) {
            $query = Topic::find()->where(['store_id' => $this->store_id, 'is_delete' => 0])->andWhere('type=:type', [':type' => $this->type]);
        } else {
            $query = Topic::find()->where(['store_id' => $this->store_id, 'is_delete' => 0]);
        }
        if ($this->user_id) {
            $photographer = Photographer::findOne(['user_id' => $this->user_id, 'is_delete' => 0, 'status' => 1]);
            if($photographer){
                $query->andWhere(['<', 'target', 2]);
            }else {
                $query->andWhere(['<>', 'target', 1]);
            }
        }
        $count = $query->count();
        $pagination = new Pagination(['totalCount' => $count, 'page' => $this->page - 1, 'pageSize' => $this->limit]);
        $list = $query->orderBy('sort ASC,addtime DESC')->limit($pagination->limit)->offset($pagination->offset)
            ->select('id,title,sub_title,cover_pic,read_count,virtual_read_count,virtual_favorite_count,addtime,layout,content')->asArray()->all();

        foreach ($list as $i => $item) {
            $read_count = intval($item['read_count'] + $item['virtual_read_count']);
            unset($list[$i]['read_count']);
            unset($list[$i]['virtual_read_count']);
            if ($read_count < 10000) {
                $read_count = $read_count . '人浏览';
            }
            if ($read_count >= 10000) {
                $read_count = intval($read_count / 10000) . '万+人浏览';
            }
            $goods_class = 'class="goods-link"';
            $goods_count = mb_substr_count($item['content'], $goods_class);
            unset($list[$i]['content']);
            $list[$i]['read_count'] = $read_count;
            if ($goods_count)
                $list[$i]['goods_count'] = $goods_count . '件宝贝';
        }
        return new ApiResponse(0, 'success', ['list' => $list]);


    }
}