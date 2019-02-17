<?php
/**
 * Created by IntelliJ IDEA.
 * User: luwei
 * Date: 2017/12/28
 * Time: 15:53
 */
$this->title = '商户列表';
$url_manager = Yii::$app->urlManager;

$user_login_url = Yii::$app->urlManager->createAbsoluteUrl(['user/passport/login', 'entry_store_id' => $this->context->store->id]);
?>
<div class="alert alert-info rounded-0">
    入驻商户PC端登录网址：
    <a href="<?= $user_login_url ?>" target="_blank"><?= $user_login_url ?></a>
</div>
<div class="panel mb-3">
    <div class="panel-header">
        <span><?= $this->title ?></span>
        <form class="form-inline d-inline-block float-right" style="margin: -.25rem 0" method="get">
            <input type="hidden" name="r" value="mch/mch/index/index">
            <div class="input-group">
                <a class="btn btn-primary mr-3" href="<?= Yii::$app->urlManager->createUrl(['mch/mch/index/add']) ?>">添加商户</a>
                <input class="form-control" name="keyword" value="<?= $get['keyword'] ?>" placeholder="店铺/用户/联系人">
                <span class="input-group-btn">
                    <button class="btn btn-secondary">搜索</button>
                </span>
            </div>
        </form>
    </div>
    <div class="panel-body">
        <?php if (!$list || count($list) == 0): ?>
            <div class="p-5 text-center text-muted">暂无商户</div>
        <?php else: ?>
            <table class="table table-bordered">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>店铺</th>
                    <th>用户</th>
                    <th>联系人</th>
                    <th>排序</th>
                    <th>开业</th>
                    <th>操作</th>
                </tr>
                </thead>
                <?php foreach ($list as $item): ?>
                    <tr>
                        <td><?= $item['id'] ?></td>
                        <td>
                            <img src="<?= $item['logo'] ?>"
                                 style="width: 25px;height: 25px;margin: -.5rem .5rem -.5rem 0">
                            <?= $item['name'] ?>
                        </td>
                        <td>
                            <img src="<?= $item['avatar_url'] ?>"
                                 style="width: 25px;height: 25px;margin: -.5rem .5rem -.5rem 0">
                            <?= $item['nickname'] ?>
                        </td>
                        <td><?= $item['realname'] ?>（<?= $item['tel'] ?>）</td>
                        <td><?= $item['sort'] ?></td>
                        <td>
                            <?php if ($item['is_open'] == 1): ?>
                                <label class="switch-label">
                                    <input type="checkbox" name="is_open" checked data-id="<?= $item['id'] ?>">
                                    <span class="label-icon"></span>
                                    <span class="label-text"></span>
                                </label>
                            <?php else: ?>
                                <label class="switch-label">
                                    <input type="checkbox" name="is_open" data-id="<?= $item['id'] ?>">
                                    <span class="label-icon"></span>
                                    <span class="label-text"></span>
                                </label>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?= $url_manager->createUrl(['mch/mch/index/edit', 'id' => $item['id']]) ?>">管理</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
            <?= \yii\widgets\LinkPager::widget(['pagination' => $pagination]) ?>
        <?php endif; ?>
    </div>
</div>
<script>
    $(document).on('change', 'input[name=is_open]', function () {
        console.log($(this));
        var id = $(this).attr('data-id');
        var status = 0;
        if ($(this).prop('checked'))
            status = 1;
        else
            status = 0;
        $.loading();
        $.ajax({
            url: '<?=Yii::$app->urlManager->createUrl(['mch/mch/index/set-open-status'])?>',
            dataType: 'json',
            data: {
                id: id,
                status: status,
            },
            success: function (res) {
                $.toast({
                    content: res.msg,
                });
            },
            complete: function () {
                $.loadingHide();
            }
        });
    });
</script>