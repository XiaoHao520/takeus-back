<?php
defined('YII_ENV') or exit('Access Denied');

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/6/27
 * Time: 11:14
 */

use yii\widgets\LinkPager;

$urlManager = Yii::$app->urlManager;
$this->title = '在线记录';
$this->params['active_nav_group'] = 2;
?>

<div class="panel mb-3">
    <div class="panel-header">
        <span><?= $this->title ?></span>

    </div>
    <div class="panel-body">
        <table class="table table-bordered bg-white">
            <thead>
            <tr>
                <th>摄影师ID</th>
                <th>摄影师姓名</th>
                <th>头像</th>
                <th>开始时间</th>
                <th>结束时间</th>
                <th>有效时长</th>
                <th>奖励金额</th>

            </tr>
            </thead>
            <col style="width: 8%">
            <col style="width: 8%">
            <col style="width: 8%">
            <col style="width: 8%">
            <col style="width: 8%">
            <col style="width: 8%">
            <col style="width: 8%">
            <tbody>
            <?php foreach ($list as $index => $item): ?>
                <tr class="nav-item1">
                    <td>

                        <span><?= $item['id'] ?></span>
                    </td>
                    <td><?= $item['name'] ?></td>
                    <td>
                        <?php if (!empty($item['header_url'])): ?>
                            <img src="<?= $item['header_url'] ?>"
                                 style="width: 20px;height: 20px;">
                        <?php endif; ?>
                    </td>
                    <td>
                        <span><?= date("Y-m-d H:i:s",$item['start']) ?></span>
                    </td>
                    <td>
                        <span><?= date("Y-m-d H:i:s",$item['end']) ?></span>
                    </td>
                    <td>
                        <span><?= $item['total']?></span>
                    </td>
                    <td>
                        <span><?= $item['total']?></span>
                    </td>

                </tr>

            <?php endforeach; ?>
            </tbody>

        </table>
        <div class="text-center">
            <?= \yii\widgets\LinkPager::widget(['pagination' => $pagination,]) ?>
            <div class="text-muted"><?= $count ?>条数据</div>
        </div>
    </div>
</div>

<script>
    $(document).on('click', '.nav-item1', function () {
        if ($(this).find(".trans")[0].style.display == 'inline-block') {
            $(this).find(".trans")[0].style.display = 'inline';
        } else {
            $(this).find(".trans")[0].style.display = 'inline-block';
        }
        $('.bg-' + $(this).index(".nav-item1")).toggle();
    });
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