<?php
/**
 * Created by IntelliJ IDEA.
 * User: luwei
 * Date: 2017/7/18
 * Time: 11:28
 */

namespace app\modules\api\controllers;


use app\hejiang\ApiResponse;
use app\hejiang\BaseApiResponse;
use app\models\Address;
use app\models\FormId;
use app\models\Level;
use app\models\Option;
use app\models\Order;
use app\models\Photographer;
use app\models\PhotographerLevel;
use app\models\Requirement;
use app\models\Setting;
use app\models\Share;
use app\models\Store;
use app\models\User;
use app\models\UserAuthLogin;
use app\models\UserCard;
use app\models\UserCenterForm;
use app\models\UserCenterMenu;
use app\models\UserFormId;
use app\modules\api\behaviors\LoginBehavior;
use app\modules\api\models\AddressDeleteForm;
use app\modules\api\models\AddressSaveForm;
use app\modules\api\models\AddressSetDefaultForm;
use app\modules\api\models\AddWechatAddressForm;
use app\modules\api\models\CardListForm;
use app\modules\api\models\FavoriteAddForm;
use app\modules\api\models\FavoriteListForm;
use app\modules\api\models\FavoriteRemoveForm;
use app\modules\api\models\OrderListForm;
use app\modules\api\models\PhotographerListForm;
use app\modules\api\models\TopicFavoriteForm;
use app\modules\api\models\TopicFavoriteListForm;
use app\modules\api\models\WechatDistrictForm;
use app\modules\api\models\QrcodeForm;
use app\modules\api\models\OrderMemberForm;
use app\models\SmsSetting;
use app\modules\api\models\UserForm;
use app\extensions\Sms;

class RequirementController extends Controller
{


    public function actionPhotographerLevel()
    {
        $level_list = PhotographerLevel::find()->where(['store_id' => $this->store_id, 'is_delete' => 0])->asArray()->all();
        return new BaseApiResponse([
            'code' => 1,
            'data' => $level_list
        ]);

    }

    public function actionRequirementTypes()
    {
        $requirement_types = Requirement::find()->where(['store_id' => $this->store_id, 'is_delete' => 0])->asArray()->all();
        return new BaseApiResponse([
            'code' => 1,
            'data' => $requirement_types
        ]);
    }

    public function actionAreaPhotographer()
    {
         $form=new PhotographerListForm();
         $u_lat=\Yii::$app->request->get('u_lat');
        $u_lon=\Yii::$app->request->get('u_lon');
        $level_id=\Yii::$app->request->get("level_id");
           $form->store_id=$this->store_id;

      //   $u_lat=\Yii::$app->request->get('u_lat');
           $form->u_lat=$u_lat;
           $form->u_lon=$u_lon;
            $form->level_id=$level_id;
            return new BaseApiResponse($form->search());



    }


}