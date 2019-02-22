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
use app\models\UserTplMsgSender;
use Curl\Curl;
class TplMsgForm extends Model
{
    public $store_id;

    public $photographer;
    public $user_id;


 public $order_id;


    public  function sendAddPriceMsg(){

        $tpl=new UserTplMsgSender($this->store_id,$this->user_id,$this->order_id,$this->getWechat());
        $tpl->addPriceMsg('增加服务费通知');

    }



}