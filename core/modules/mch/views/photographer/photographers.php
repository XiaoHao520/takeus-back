<?php
defined('YII_ENV') or exit('Access Denied');

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/6/29
 * Time: 9:50
 */

use yii\widgets\LinkPager;

$urlManager = Yii::$app->urlManager;
$imgurl = Yii::$app->request->baseUrl;
$this->title = '摄影师列表';
$this->params['active_nav_group'] = 2;
?>
<style>
    .modal-dialog{
        position:fixed;
        top:20%;
        left:45%;
        width:240px;
    }
    .modal-content{
        width:240px;
    }
    .modal-body{
        /*height:200px;*/
    }
    table {
        table-layout: fixed;
    }

    th {
        text-align: center;
        white-space: nowrap;
        text-overflow: ellipsis;
        overflow: hidden;
    }

    td {
        text-align: center;
    }

    .ellipsis {
        white-space: nowrap;
        text-overflow: ellipsis;
        overflow: hidden;
    }

    td.nowrap {
        white-space: nowrap;
        overflow: hidden;
    }

    .goods-pic {
        width: 3rem;
        height: 3rem;
        display: inline-block;
        background-color: #ddd;
        background-size: cover;
        background-position: center;
    }
</style>

<div class="panel mb-3">
    <div class="panel-header"><?= $this->title ?></div>
    <div class="panel-body">
        <?php
        $status = ['未认证', '已认证'];
        ?>
        <div class="mb-3 clearfix">
            <div class="float-left" hidden>
                <a href="<?= $urlManager->createUrl(['mch/goods/goods-edit']) ?>" class="btn btn-primary"><i
                            class="iconfont icon-playlistadd"></i>添加商品</a>
                <!--
                <a href="javascript:void(0)" class="btn btn-secondary batch"
                   data-url="<?= $urlManager->createUrl(['mch/goods/batch']) ?>" data-content="是否批量上架"
                   data-type="0">批量上架</a>
                <a href="javascript:void(0)" class="btn btn-warning batch"
                   data-url="<?= $urlManager->createUrl(['mch/goods/batch']) ?>" data-content="是否批量下架"
                   data-type="1">批量下架</a>
                <a href="javascript:void(0)" class="btn btn-danger batch"
                   data-url="<?= $urlManager->createUrl(['mch/goods/batch']) ?>" data-content="是否批量删除"
                   data-type="2">批量删除</a>
                <a href="javascript:" data-toggle="modal" data-target="#attrAddModal" class="btn btn-primary">批量设置积分</a>
                -->
                <div class="dropdown float-right ml-2">
                    <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton"
                            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        批量设置
                    </button>
                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton"
                         style="max-height: 200px;overflow-y: auto" hidden>
                        <a href="javascript:void(0)" class="btn btn-secondary batch dropdown-item"
                           data-url="<?= $urlManager->createUrl(['mch/goods/batch']) ?>" data-content="是否批量上架"
                           data-type="0">批量上架</a>
                        <a href="javascript:void(0)" class="btn btn-warning batch dropdown-item"
                           data-url="<?= $urlManager->createUrl(['mch/goods/batch']) ?>" data-content="是否批量下架"
                           data-type="1">批量下架</a>
                        <a href="javascript:void(0)" class="btn btn-danger batch dropdown-item"
                           data-url="<?= $urlManager->createUrl(['mch/goods/batch']) ?>" data-content="是否批量删除"
                           data-type="2">批量删除</a>
                        <a href="javascript:void(0)" class="btn btn-danger batch dropdown-item"
                           data-url="<?= $urlManager->createUrl(['mch/goods/batch']) ?>" data-content="是否批量加入快速购买"
                           data-type="3">批量加入快速购买</a>
                        <a href="javascript:void(0)" class="btn btn-danger batch dropdown-item"
                           data-url="<?= $urlManager->createUrl(['mch/goods/batch']) ?>" data-content="是否批量关闭快速购买"
                           data-type="4">批量关闭快速购买</a>
                        <a href="javascript:" data-toggle="modal" data-target="#attrAddModal" class="btn btn-primary dropdown-item">批量设置积分</a>
                    </div>
                </div>
                <div class="dropdown float-right ml-2">
                    <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton"
                            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <?= isset($_GET['cat']) ? $_GET['cat'] : '全部类型' ?>
                    </button>
                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton"
                         style="max-height: 200px;overflow-y: auto">
                        <a class="dropdown-item" href="<?= $urlManager->createUrl(['mch/goods/goods']) ?>">全部类型</a>
                       <!-- <?php /*foreach ($cat_list as $index => $value): */?>
                            <a class="dropdown-item"
                               href="<?/*= $urlManager->createUrl(array_merge(['mch/goods/goods'], $_GET, ['cat' => $value])) */?>"><?/*= $value */?></a>
                        --><?php /*endforeach; */?>
                    </div>
                </div>

                <div class="dropdown float-right ml-2">
                    <button class="btn btn-secondary dropdown-toggle" type="button"
                            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <?php if($_GET['status']==='1'): ?>上架
                                <?php elseif($_GET['status']==='0'): ?>下架
                                <?php elseif($_GET['status']==''): ?>全部商品
                                <?php else: ?>
                                <?php endif; ?>
                    </button>
                    <div class="dropdown-menu" style="min-width:8rem"
                        >
                        <a class="dropdown-item" href="<?= $urlManager->createUrl(['mch/goods/goods']) ?>">全部商品</a>            
                        <a class="dropdown-item" href="<?= $urlManager->createUrl(['mch/goods/goods','status' => 1]) ?>">上架</a>
                        <a class="dropdown-item" href="<?= $urlManager->createUrl(['mch/goods/goods','status' => 0]) ?>">下架</a>

                    </div>
                </div>
            </div>
            <div class="float-right">
                <form method="get">

                    <?php $_s = ['keyword','page','per-page'] ?>
                    <?php foreach ($_GET as $_gi => $_gv):if (in_array($_gi, $_s)) continue; ?>
                        <input type="hidden" name="<?= $_gi ?>" value="<?= $_gv ?>">
                    <?php endforeach; ?>

                    <div class="input-group">
                        <input class="form-control" placeholder="摄影师名字" name="keyword"
                               value="<?= isset($_GET['keyword']) ? trim($_GET['keyword']) : null ?>">
                        <span class="input-group-btn">
                    <button class="btn btn-primary">搜索</button>
                </span>
                    </div>
                </form>
            </div>
        </div>
        <table class="table table-bordered bg-white">
            <thead>
            <tr>
                <th style="text-align: left;">
                    <label class="checkbox-label">
                        <input type="checkbox" class="goods-all">
                        <span class="label-icon"></span>
                        <span class="label-text">ID</span>
                    </label>
                </th>
                <th>级别</th>
                <th>微信昵称</th>
                <th>姓名</th>
               <th>头像</th>
                <th>身份证</th>
                <th>简介</th>
                <th>摄影师的位置</th>
                 <th>作品</th>
                <th>状态</th>

                <th>申请时间</th>
                <th>操作</th>
            </tr>
            </thead>
            <col style="width: 5%">
            <col style="width: 7%">
            <col style="width: 7%">
            <col style="width: 7%">
            <col style="width: 8%">
            <col style="width: 15%">
            <col style="width: 15%">
            <col style="width: 8%">  <col style="width: 20%">

            <col style="width: 10%">
            <col style="width: 5%">
            <col style="width: 8%">

            <tbody>
            <?php foreach ($list as $index => $photographer): ?>
                <tr>
                    <td class="nowrap" style="text-align: left;" data-toggle="tooltip"
                        data-placement="top" title="<?=$photographer['id']?>">
                        <label class="checkbox-label">
                            <input data-num="<?/*= $goods->num */?>" type="checkbox"
                                   class="goods-one"
                                   value="<?= $photographer['id'] ?>">
                            <span class="label-icon"></span>
                            <span class="label-text"><?= $photographer['id'] ?></span>
                        </label>
                    </td>
                 <td>   <?= $photographer['level']  ?></td>
                    <td>   <?= $photographer['nickname']  ?></td>
                    <td>
                        <?= $photographer['name']  ?>
                    </td>
                    <td>
                        <img src="<?= $photographer['header_url']  ?>" style="width: 100px;height: 100px"/>
                    </td>
                    <td>
                        <img src="<?= $photographer['id_card']  ?>" style="width: 100px;height: 100px"/>
                    </td>
                    <td>
                        <?= $photographer['details']  ?>
                    </td>
                    <td>
                        <?= $photographer['address']  ?>
                    </td>


                    <td>
                        <?php foreach ($photographer['product_list'] as $item):?>
                            <img src="<?=$item['pic_url']?>" alt="" style="height: 100px;width: 100px">
                        <?endforeach;?>
                    </td>




                    <td class="nowrap">
                        <?php if ($photographer['status'] == 1): ?>
                            <span class="badge badge-success"><?= $status[$photographer['status'] ] ?></span>
                            |
                            <a href="javascript:" onclick="upDown(<?= $photographer['id']  ?>,'down');">取消认证</a>
                        <?php else: ?>
                            <span class="badge badge-default"><?= $status[$photographer['status'] ] ?></span>
                            |
                            <a href="javascript:" onclick="upDown(<?= $photographer['id']  ?>,'up');">认证</a>
                        <?php endif ?>
                    </td>

                    <td>
                        <?= date('Y-m-d H:i:s',$photographer['addtime']) ?>
                    </td>
                    <td class="nowrap">

                       <!-- <img src="<?/*= $imgurl */?>\statics\images\chengxuma.png" width="20px"  onclick="getGoodsQrcode(<?/*= $goods->id */?>);" data-toggle="modal" data-target="#myModal" title="小程序码">&nbsp;
-->
                   <!--     <a class="btn btn-sm btn-primary"
                           href="<?/*= $urlManager->createUrl(['mch/photographer/photographer-edit', 'id' => $photographer['id']]) */?>">修改</a>
-->
                        <a class="btn btn-sm btn-danger del"
                           href="<?= $urlManager->createUrl(['mch/photographer/photographer-del', 'id' => $photographer['id']]) ?>">删除</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>

        </table>
        <nav aria-label="Page navigation example">
            <?php echo LinkPager::widget([
                'pagination' => $pagination,
                'prevPageLabel' => '上一页',
                'nextPageLabel' => '下一页',
                'firstPageLabel' => '首页',
                'lastPageLabel' => '尾页',
                'maxButtonCount' => 5,
                'options' => [
                    'class' => 'pagination',
                ],
                'prevPageCssClass' => 'page-item',
                'pageCssClass' => "page-item",
                'nextPageCssClass' => 'page-item',
                'firstPageCssClass' => 'page-item',
                'lastPageCssClass' => 'page-item',
                'linkOptions' => [
                    'class' => 'page-link',
                ],
                'disabledListItemSubTagOptions' => ['tag' => 'a', 'class' => 'page-link'],
            ])
            ?>
        </nav>

    </div>
</div>


<!--添加规格-->
<div class="modal fade" id="attrAddModal" data-backdrop="static">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">积分设置</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group mb-3">
                    <div class="input-group short-row">
                        <input type="text" step="1" class="form-control short-row" name="integral[give]"
                               value="" placeholder="积分赠送">
                        <span class="input-group-addon">分</span>
                    </div>
                    <div class="fs-sm text-muted">
                        会员购物赠送的积分, 如果不填写或填写0，则默认为不赠送积分，如果带%则为按成交价格的比例计算积分
                    </div>
                </div>
                <div class="form-group mb-3">
                    <div class="input-group short-row">
                        <span class="input-group-addon">最多抵扣</span>
                        <input type="text" step="1" class="form-control short-row" name="integral[forehead]"
                               value="" placeholder="积分抵扣">
                        <span class="input-group-addon">元</span>
                    </div>
                    <div class="input-group short-row">
                        <label class="custom-control custom-checkbox">
                            <input value="1"
                                                                                             name="integral[more]"
                                                                                             type="checkbox"
                                                                                             class="custom-control-input">
                            <span class="custom-control-indicator"></span>
                            <span class="custom-control-description">允许多件累计折扣</span>
                        </label>
                    </div>
                    <div class="fs-sm text-muted">
                        如果设置0，则不支持积分抵扣 如果带%则为按成交价格的比例计算抵扣多少元
                    </div>
                </div>

                <div class="form-error text-danger mt-3 modelError" style="display: none">ddd</div>
                <div class="form-success text-success mt-3" style="display: none">sss</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary save-attr-btn">提交</button>
            </div>
        </div>
    </div>
</div>

<!--小程序码开始-->

<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" data-backdrop="static" data-show="false">

    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body">
                <img id="goods_qrcode" src="" width="200px">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default"
                        data-dismiss="modal" id="closeModel">关闭
                </button>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<!--小程序码结束-->


<script>
    $("#myModal").modal({backdrop: "static", keyboard: false});

    $("#closeModel").click(function(){
        $("#goods_qrcode").attr("src",'');
    });

    var GoodsQrcodeUrl = "<?= $urlManager->createUrl(['mch/goods/goods-qrcode']) ?>";

    function getGoodsQrcode(id){
        $.ajax({
            url: GoodsQrcodeUrl,
            type: 'get',
            dataType: 'json',
            data: {
                goods_id:id
            },
            success: function (res) {
                if (res.code == 0) {
                    $("#goods_qrcode").attr("src",res.data.pic_url);
                }else{
                    alert('获取小程序码失败');
                }
            }
        });
    }

    $(document).on('click', '.del', function () {
        if (confirm("是否删除？")) {
            $.ajax({
                url: $(this).attr('href'),
                type: 'get',
                dataType: 'json',
                success: function (res) {
                    alert(res.msg);
                    if (res.code == 0) {
                        window.location.reload();
                    }
                }
            });
        }
        return false;
    });

    function upDown(id, type) {
        var text = '';
        if (type == 'up') {
            text = "认证";
        }else if(type == 'start'){
            text = "加入快速购买";
        }else if(type == 'close'){
            text = "关闭快速购买";
        }else {
            text = '未认证';
        }

        var url = "<?= $urlManager->createUrl(['mch/photographer/photographer-up-down']) ?>";
        if (confirm("是否" + text + "？")) {
            $.ajax({
                url: url,
                type: 'get',
                dataType: 'json',
                data: {id: id, type: type},
                success: function (res) {
                    if (res.code == 0) {
                        window.location.reload();
                    }
                    if (res.code == 1) {
                        alert(res.msg);
                        if (res.return_url) {
                            location.href = res.return_url;
                        }
                    }
                }
            });
        }
        return false;
    }

    $(document).on('click', '.goods-all', function () {
        var checked = $(this).prop('checked');
        $('.goods-one').prop('checked', checked);
        if (checked) {
            $('.batch').addClass('is_use');
        } else {
            $('.batch').removeClass('is_use');
        }
    });
    $(document).on('click', '.goods-one', function () {
        var checked = $(this).prop('checked');
        var all = $('.goods-one');
        var is_all = true;//只要有一个没选中，全选按钮就不选中
        var is_use = false;//只要有一个选中，批量按妞就可以使用
        all.each(function (i) {
            if ($(all[i]).prop('checked')) {
                is_use = true;
            } else {
                is_all = false;
            }
        });
        if (is_all) {
            $('.goods-all').prop('checked', true);
        } else {
            $('.goods-all').prop('checked', false);
        }
        if (is_use) {
            $('.batch').addClass('is_use');
        } else {
            $('.batch').removeClass('is_use');
        }
    });
    $(document).on('click', '.batch', function () {
        var all = $('.goods-one');
        var is_all = true;//只要有一个没选中，全选按钮就不选中
        all.each(function (i) {
            if ($(all[i]).prop('checked')) {
                is_all = false;
            }
        });
        if (is_all) {
            $.myAlert({
                content: "请先勾选商品"
            });
        }
    });
    // 批量设置积分
    $(document).on('click', '.save-attr-btn', function () {
        var give = $('input[name^="integral[give]"]').val();
        var forehead = $('input[name^="integral[forehead]"]').val();
//        var more = $('input[name^="integral[more]"]').val();
        if ($('input[name^="integral[more]"]').is(':checked')) {
            var more = 1;
        } else {
            var more = '';
        }
        console.log(more);
        var all = $('.goods-one');
        var is_all = true;//只要有一个没选中，全选按钮就不选中
        all.each(function (i) {
            if ($(all[i]).prop('checked')) {
                is_all = false;
            }
        });
        if (is_all) {
            $.myAlert({
                content: "请先勾选商品"
            });
            return;
        }
        var a = $(this);
        var goods_group = [];
        all.each(function (i) {
            if ($(all[i]).prop('checked')) {
                var goods = {};
                goods_group.push($(all[i]).val());
            }
        });

        $.ajax({
            url: "<?= Yii::$app->urlManager->createUrl(['mch/goods/batch-integral']) ?>",
            type: 'get',
            dataType: 'json',
            data: {
                goods_group: goods_group,
                give: give,
                forehead: forehead,
                more: more,
            },
            success: function (res) {
                if (res.code == 0) {
                    window.location.reload();
                } else {
                    $('.modelError').text(res.msg);
                    $('.modelError').css('display', 'block');
                }
            },
//            complete: function () {
//                $.myLoadingHide();
//            }
        });


    });
    $(document).on('click', '.is_use', function () {
        var a = $(this);
        var goods_group = [];
        var all = $('.goods-one');
        all.each(function (i) {
            if ($(all[i]).prop('checked')) {
                var goods = {};
                goods.id = $(all[i]).val();
                goods.num = $(all[i]).data('num');
                goods_group.push(goods);
            }
        });
        $.myConfirm({
            content: a.data('content'),
            confirm: function () {
                $.myLoading();
                $.ajax({
                    url: a.data('url'),
                    type: 'get',
                    dataType: 'json',
                    data: {
                        goods_group: goods_group,
                        type: a.data('type'),
                    },
                    success: function (res) {
                        if (res.code == 0) {
                            $.myAlert({
                                content:res.msg,
                                confirm:function(){
                                    window.location.reload();
                                }
                            });
                        } else {
                            $.myAlert({
                                content:res.msg
                            });
                        }
                    },
                    complete: function () {
                        $.myLoadingHide();
                    }
                });
            }
        })
    });
</script>
<script>
    $(document).ready(function () {
        var clipboard = new Clipboard('.copy');
        clipboard.on('success', function (e) {
            $.myAlert({
                title: '提示',
                content: '复制成功'
            });
        });
        clipboard.on('error', function (e) {
            $.myAlert({
                title: '提示',
                content: '复制失败，请手动复制。链接为：' + e.text
            });
        });
    })
</script>