<?php
defined('YII_ENV') or exit('Access Denied');
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/11
 * Time: 10:33
 */

/* @var $pagination yii\data\Pagination */

use yii\widgets\LinkPager;
use \app\models\Cash;

$urlManager = Yii::$app->urlManager;
$this->title = '提现列表';
$this->params['active_nav_group'] = 5;
$status = Yii::$app->request->get('status');
if ($status === '' || $status === null || $status == -1)
    $status = -1;
?>
<div class="panel mb-3">
    <div class="panel-header"><?= $this->title ?></div>
    <div class="panel-body">
        <div class="mb-3 clearfix">
            <div class="p-4 bg-shaixuan">
                <form method="get">

                    <?php $_s = ['keyword'] ?>
                    <?php foreach ($_GET as $_gi => $_gv):if (in_array($_gi, $_s)) continue; ?>
                        <input type="hidden" name="<?= $_gi ?>" value="<?= $_gv ?>">
                    <?php endforeach; ?>
                    <div flex="dir:left">
                        <div>
                            <div class="input-group">
                                <input class="form-control"
                                       placeholder="姓名/微信昵称"
                                       name="keyword"
                                       autocomplete="off"
                                       value="<?= isset($_GET['keyword']) ? trim($_GET['keyword']) : null ?>">
                                <span class="input-group-btn">
                    <button class="btn btn-primary">搜索</button>
                </span>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

        </div>
        <div class="mb-4">
            <ul class="nav nav-tabs status">
                <li class="nav-item">
                    <a class="status-item nav-link <?= $status == -1 ? 'active' : null ?>"
                       href="<?= $urlManager->createUrl(['mch/user/cash']) ?>">全部</a>
                </li>
                <li class="nav-item">
                    <a class="status-item nav-link <?= $status == 0 ? 'active' : null ?>"
                       href="<?= $urlManager->createUrl(['mch/user/cash', 'status' => 0]) ?>">未审核<?= $count['count_1'] ? "(" . $count['count_1'] . ")" : null ?></a>
                </li>
                <li class="nav-item">
                    <a class="status-item nav-link <?= $status == 1 ? 'active' : null ?>"
                       href="<?= $urlManager->createUrl(['mch/user/cash', 'status' => 1]) ?>">待打款<?= $count['count_2'] ? "(" . $count['count_2'] . ")" : null ?></a>
                </li>
                <li class="nav-item">
                    <a class="status-item nav-link <?= $status == 2 ? 'active' : null ?>"
                       href="<?= $urlManager->createUrl(['mch/user/cash', 'status' => 2]) ?>">已打款<?= $count['count_3'] ? "(" . $count['count_3'] . ")" : null ?></a>
                </li>
                <li class="nav-item">
                    <a class="status-item nav-link <?= $status == 3 ? 'active' : null ?>"
                       href="<?= $urlManager->createUrl(['mch/user/cash', 'status' => 3]) ?>">无效<?= $count['count_4'] ? "(" . $count['count_4'] . ")" : null ?></a>
                </li>
            </ul>
        </div>
        <table class="table table-bordered bg-white">
            <tr>
                <td width="50px">ID</td>
                <td width="200px">微信信息</td>

                <td>提现金额（元）</td>
                <td>实际打款（元）</td>
                <td>联系人</td>
                <td>联系微信</td>
                <td>状态</td>
                <td>申请时间</td>
                <td>操作</td>
            </tr>
            <?php foreach ($list as $index => $value): ?>
                <tr>
                    <td><?= $value['user_id'] ?></td>
                    <td data-toggle="tooltip" data-placement="top" title="<?= $value['nickname'] ?>">
                        <span
                                style="width: 150px;display:block;white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><img
                                    src="<?= $value['avatar_url'] ?>"
                                    style="width: 30px;height: 30px;margin-right: 10px;"><?= $value['nickname'] ?></span>
                    </td>

                    <td><?= $value['price'] ?></td>
                    <td><?= $value['real_price'] ?></td>
                    <td><?=  $value['name'] ?></td>

                    <td><?=  $value['mobile'] ?></td>
                    <td>
                        <?php if ($value['pay_type'] != 1): ?>
                            <?= Cash::$status[$value['status']] ?><?= ($value['status'] == 2) ? "(" . Cash::$type[$value['type']] . ")" : "" ?>
                            <?php if ($value['status'] == 5): ?>
                                <span>已打款</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <?= Cash::$status[$value['status']] ?><?= ($value['status'] == 2) ? "(微信自动打款)" : "" ?>
                        <?php endif; ?>
                    </td>
                    <td><?= date('Y-m-d H:i', $value['addtime']); ?></td>

                    <td>
                        <?php if ($value['status'] == 0): ?>
                            <a class="btn btn-sm btn-primary del" href="javascript:"
                               data-url="<?= $urlManager->createUrl(['mch/user/apply', 'status' => 1, 'id' => $value['id']]) ?>"
                               data-content="是否通过申请？">通过</a>
                            <a class="btn btn-sm btn-danger del" href="javascript:"
                               data-url="<?= $urlManager->createUrl(['mch/user/apply', 'status' => 3, 'id' => $value['id']]) ?>"
                               data-content="是否驳回申请？">驳回</a>
                        <?php elseif ($value['status'] == 1): ?>
                            <div>
                                <a class="btn btn-sm btn-danger del" href="javascript:"
                                   data-url="<?= $urlManager->createUrl(['mch/user/apply', 'status' => 3, 'id' => $value['id']]) ?>"
                                   data-content="是否驳回申请？">驳回</a>
                            </div>
                            <div class="mt-2">
                                <a class="btn btn-sm btn-primary pay" href="javascript:"
                                   data-url="<?= $urlManager->createUrl(['mch/user/confirm', 'status' => 2, 'id' => $value['id']]) ?>"
                                   data-content="是否确认打款？">微信打款</a>

                                <span>（微信支付自动打款）</span>
                            </div>
                            <div class="mt-2">
                                <a class="btn btn-sm btn-primary pay" href="javascript:"
                                   data-url="<?= $urlManager->createUrl(['mch/user/confirm', 'status' => 2, 'id' => $value['id'],'is_down'=>1]) ?>"
                                   data-content="是否确认打款？">线下打款</a>

                                <span>（线下手动打款）</span>
                            </div>

                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        <div class="text-center">
            <?= LinkPager::widget(['pagination' => $pagination,]) ?>
            <div class="text-muted"><?= $pagination->totalCount ?>条数据</div>
        </div>
    </div>
</div>
<?= $this->render('/layouts/ss'); ?>
<script>
    $(document).on('click', '.del', function () {
        var a = $(this);
        if (confirm(a.data('content'))) {
            a.btnLoading();
            $.ajax({
                url: a.data('url'),
                type: 'get',
                dataType: 'json',
                success: function (res) {
                    if (res.code == 0) {
                        window.location.reload();
                    } else {
                        $.myAlert({
                            content: res.msg
                        });
                        a.btnReset();
                    }
                }
            });
        }
        return false;
    });
</script>
<script>
    $(document).on('click', '.pay', function () {
        var a = $(this);
        var btn = $('.pay');
        if (confirm(a.data('content'))) {
            btn.btnLoading();
            $.ajax({
                url: a.data('url'),
                type: 'get',
                dataType: 'json',
                success: function (res) {
                    if (res.code == 0) {
                        window.location.reload();
                    } else {
                        $.myAlert({
                            content: res.msg
                        });
                        btn.btnReset();
                    }
                }
            });
        }
        return false;
    });
</script>