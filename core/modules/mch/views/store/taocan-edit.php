<?php
defined('YII_ENV') or exit('Access Denied');
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/6/27
 * Time: 11:36
 */

$urlManager = Yii::$app->urlManager;
$this->title = '分类编辑';
$this->params['active_nav_group'] = 1;
?>
<div class="panel mb-3">
    <div class="panel-header"><?= $this->title ?></div>
    <div class="panel-body">
        <form class="auto-form" method="post" return="<?= $urlManager->createUrl(['mch/store/taocan']) ?>">


            <div class="form-group row">
                <div class="form-group-label col-sm-2 text-right">
                    <label class="col-form-label required">需求分类名称</label>
                </div>
                <div class="col-sm-6">
                    <input class="form-control" type="text" name="model[name]" value="<?= $list['name'] ?>">
                </div>
            </div>

            <div class="form-group row">
                <div class="form-group-label col-sm-2 text-right">
                    <label class="col-form-label required">摄影师级别</label>
                </div>
                <div class="col-sm-6">

                    <select name="model[level_id]" id="" class="form-control">

                          <?php foreach($level_list as $item):?>
                        <option value="<?=$item->id?>" selected="<?php $item->id==$list['level_id']?'selected':''?>"><?=$item->name?></option>
                          <?php endforeach;?>
                    </select>
                </div>
            </div>



            <div class="form-group row">
                <div class="form-group-label col-sm-2 text-right">
                    <label class="col-form-label required">需求类型</label>
                </div>
                <div class="col-sm-6">
                    <select name="model[requirement_id]" id="" class="form-control">
                        <?php foreach($requirement_list as $item):?>
                            <option value="<?=$item->id?>" selected="<?php $item->id==$list['requirement_id']?'selected':''?>"><?=$item->name?></option>
                        <?php endforeach;?>
                    </select>
                </div>
            </div>


            <div class="form-group row">
                <div class="form-group-label col-sm-2 text-right">
                    <label class="col-form-label required">时长（分钟）</label>
                </div>
                <div class="col-sm-6">
                    <input class="form-control" type="number" step="1" name="model[minutes]" value="<?= $list['minutes'] ?>">
                </div>
            </div>


            <div class="form-group row">
                <div class="form-group-label col-sm-2 text-right">
                    <label class="col-form-label required">照片数量（张）</label>
                </div>
                <div class="col-sm-6">
                    <input class="form-control" type="number" step="1" name="model[number]" value="<?= $list['number'] ?>">
                </div>
            </div>




            <div class="form-group row">
                <div class="form-group-label col-sm-2 text-right">
                    <label class="col-form-label required">套餐价格</label>
                </div>
                <div class="col-sm-6">
                    <input class="form-control" type="number" step="0.01" name="model[price]" value="<?= $list['price'] ?>">
                </div>
            </div>


            <div class="form-group row">
                <div class="form-group-label col-sm-2 text-right">
                </div>
                <div class="col-sm-6">
                    <a class="btn btn-primary auto-form-btn" href="javascript:">保存</a>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    $(document).on('change', '.parent', function () {
        var p = $(this).val();
        if (p == '0') {
            $('.advert').show();
        } else {
            $('input[name="model[advert_url]"]').val('').trigger('change');
            $('input[name="model[advert_pic]"]').val('').trigger('change');
            $('input[name="model[advert_pic]"]').next('.image-picker-view').css('background-image', 'url("")');
            $('.advert').hide();
        }
    })
</script>