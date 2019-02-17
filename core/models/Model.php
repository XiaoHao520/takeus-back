<?php
/**
 * Created by IntelliJ IDEA.
 * User: luwei
 * Date: 2017/6/14
 * Time: 9:36
 */

namespace app\models;

/**
 * Common model class
 * 
 * @property array|false $validationError
 */
class Model extends \yii\base\Model
{
    /**
     * Get model error response
     * @param Model $model
     * @return \app\hejiang\ValidationErrorResponse
     */
    public function getErrorResponse($model = null)
    {
        if (!$model) $model = $this;
        return new \app\hejiang\ValidationErrorResponse($model->errors);
    }
}