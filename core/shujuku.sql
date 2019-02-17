/*2.4.1*/

UPDATE `hjmall_goods` SET sort = 1000 WHERE sort IS NULL;

ALTER TABLE `hjmall_goods` MODIFY COLUMN `sort`  int(11) NOT NULL DEFAULT 1000 COMMENT '排序  升序', MODIFY COLUMN `virtual_sales`  int(11) NOT NULL DEFAULT 0 COMMENT '虚拟销量';

ALTER TABLE `hjmall_goods` ADD COLUMN `mch_sort`  int NOT NULL DEFAULT 1000 COMMENT '多商户自己的排序';

UPDATE `hjmall_goods` SET mch_sort = sort, sort = 1100 WHERE mch_id != 0 AND mch_sort = 1000 AND sort != 1000 AND sort IS NOT NULL;

ALTER TABLE `hjmall_integral_log` ADD COLUMN `type`  int(11) NOT NULL DEFAULT 0 COMMENT '数据类型 0--积分修改 1--余额修改';