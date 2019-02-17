<?php

namespace app\models;

use Yii;
/**
 * This is the model class for table "{{%wechat_template_message}}".
 *
 * @property integer $id
 * @property integer $store_id
 * @property string $pay_tpl
 * @property string $send_tpl
 * @property string $refund_tpl
 * @property string $not_pay_tpl
 * @property string $revoke_tpl
 * @property string $receive_tpl
 * @property string $new_order_tpl
 * @property string $pay_service_tpl
 * @property string $add_service_tpl
 * @property string $order_finish_tpl
 */
class WechatTemplateMessage extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%wechat_template_message}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['store_id'], 'required'],
            [['store_id'], 'integer'],
            [['pay_tpl', 'send_tpl', 'refund_tpl', 'not_pay_tpl', 'revoke_tpl', 'receive_tpl', 'new_order_tpl', 'pay_service_tpl', 'add_service_tpl', 'order_finish_tpl'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'store_id' => 'Store ID',
            'pay_tpl' => '支付通知模板消息id',
            'send_tpl' => '发货通知模板消息id',
            'refund_tpl' => '退款通知模板消息id',
            'not_pay_tpl' => '订单未支付通知模板消息id',
            'revoke_tpl' => '订单取消通知模板消息id',
            'receive_tpl' => '接单',
            'new_order_tpl' => '摄影师新订单',
            'pay_service_tpl' => '支付服务费',
            'add_service_tpl' => '增加服务费',
            'order_finish_tpl' => '订单完成',
        ];
    }
}