# Reagently — Usage & Reservation Transaction Patch（中文版）

## 包含的代码

**将压缩包中 `db.php` 放在项目根目录；将 `db_connections/` 和 `user/` 下同名文件分别复制到项目对应的文件夹。** 不要删除项目里其他文件。

### DB connections（后端）
- `db_connections/insert_reservation.php`：预约预览、不锁行等待用户；生成一次性确认 token。
- `db_connections/confirm_partial_reservation.php`：使用一次性 token 防止重复提交；事务内先锁 Lot (`FOR UPDATE`)，再查权限与 ACTIVE 预约；创建预约、RESERVE 物理库存增减为 0 的记录、必要时缺货请求；全部一次提交。
- `db_connections/cancel_reservation.php`：只能 POST + CSRF；事务里先锁 Lot，再把 ACTIVE -> CANCELLED、插入 CANCEL history；物理数量不变。
- **`db_connections/insert_usage.php`（新文件）**：POST + CSRF，先锁 Lot，再锁预约，核对本人和 ACTIVE 且 used <= reserved；一次事务执行 `ACTIVE -> COMPLETED`、实体库存扣除实际使用量、写 USAGE 负数记录；剩余预约自然释放。
- `db_connections/update_quantity.php`：仅 Admin 可调用；同样先锁 Lot，库存不能调低于已预约总数；修改与 ADJUST history 原子提交。
- `db_connections/update_reagent.php`：限制管理员权限，属于前一版修正。

### 前端页面
- `user/record_usage.php`：新增隐藏 CSRF token，继续提交到 `db_connections/insert_usage.php`。
- `user/reservations.php`：把取消预约的 GET 链接换为 POST + CSRF 表单。
- `user/make_reservation.php`、`user/request_order.php`、`user/transactions.php`：保留原业务逻辑，移除开头 HTML 注释，以免登录跳转时出现 `headers already sent`。
- `db.php`：同样修正文件最前面的 HTML 注释。

## 无需迁移数据库

本补丁基于你上传的 `create_tables.sql`：`reagent_lots.current_quantity` 是实体库存；`reservations` 状态 ACTIVE 的数量之和是已预约数量；`inventory_transactions` 记录库存变化。没有引入 `usages` 表。

**核心关系：** `available = current_quantity - SUM(ACTIVE quantity_reserved)`

**记录一次使用：** 预约 ACTIVE -> COMPLETED；`current_quantity -= quantity_used`；`inventory_transactions` 插入一条 USAGE（`quantity_change = -quantity_used`）。同时 `ACTIVE` 总预约减少原来的 `quantity_reserved`，因此没有使用的部分自动变为可用。对同一个 reservation，第二次请求会因状态已 COMPLETED 而被拒绝。

**重要：** `quantity_change` 表示实际 physical stock 变化，不是 availability delta。RESERVE/CANCEL 均为 0；USAGE 为负数；ADJUST 为物理库存差值。检查其他独立写入 history 的脚本是否遵循同一语义。

## 测试步骤（在 MAMP 中）

测试前把原文件做 Git commit 或备份；在测试数据库运行；确认关键表使用 InnoDB：

```sql
SHOW TABLE STATUS WHERE Name IN ('reagent_lots','reservations','inventory_transactions','order_requests');
```

1. 找一个测试 Lot，physical = 10.00，没有 ACTIVE 预约。
2. 用户 A 预览并确认预约 8.00：reserved = 8，physical = 10，available = 2，RESERVE 记 0.00。
3. 用户 B 同时预览并确认预约 8.00：两人合计预约最多 10，其中一个会发生部分预约，例如 2.00 + order 6.00，不能超额。需要实际双浏览器并发测试才能声称通过。
4. 对 A 的 8.00 预约，打开 Record Usage，输入 5.00：该预约 COMPLETED，physical 变成 5.00；B 的 2.00 仍 ACTIVE；available 变成 3.00；USAGE 记 -5.00。
5. 再向 `insert_usage.php` 重发同一个表单：应提示已经处理，不能再次扣减库存，也不能重复记 USAGE。
6. 取消 B 的 2.00 预约：状态 CANCELLED，physical 仍 5.00；available 变成 5.00；CANCEL 记 0.00。
7. 用户管理员修改一个 Lot 的 `current_quantity` 小于 ACTIVE 总量：应拒绝；合法更改应同时写一条 ADJUST。
8. 在预约预览按两次 Yes 或刷新确认的 POST：同一 token 最多生效一次；需要重新打开预约流程才能再预约。

### 数据核验 SQL（修改 `123` 为测试 lot_id）

```sql
SELECT l.lot_id, l.current_quantity AS physical,
 COALESCE(SUM(CASE WHEN r.status = 'ACTIVE' THEN r.quantity_reserved ELSE 0 END),0) AS reserved,
 l.current_quantity - COALESCE(SUM(CASE WHEN r.status = 'ACTIVE' THEN r.quantity_reserved ELSE 0 END),0) AS available
FROM reagent_lots l LEFT JOIN reservations r ON l.lot_id = r.lot_id
WHERE l.lot_id = 123 GROUP BY l.lot_id, l.current_quantity;

SELECT reservation_id, COUNT(*) AS usage_count
FROM inventory_transactions
WHERE transaction_type = 'USAGE' AND reservation_id IS NOT NULL
GROUP BY reservation_id HAVING COUNT(*) > 1;
```

## 注意事项

- 此代码在当前环境执行 PHP 语法检查；**无法连接你的本地 MAMP/MySQL，故没有真实运行数据库集成测试。**
- 所有修改 Lot 库存或 ACTIVE 预约的其他脚本都必须先锁相同 Lot，确保一致顺序；例如其他库存入库、弃置脚本需检查。
- 本补丁的“仅一次 usage”是在所有操作遵循本后端的条件下通过 `ACTIVE -> COMPLETED` 检查实现。数据库目前没有 `usages` 表的 `UNIQUE(reservation_id)` 约束；若希望即使其他程序绕开这些后端也能强制一次性，需要后续建表或约束。
- 还未实现 Reagently 平台管理员/公司数据隔离与 Category / Nickname 管理；需要专门的数据库迁移和权限设计。
- `init.php` 当前 `BASE_URL = '/'`，本补丁沿用此设定；若项目不部署在 URL 根路径，需要同步调整 BASE_URL。
- 订单请求的最终 `insert_order_request.php`、入库/弃置逻辑尚未上传，本包没有审查它们的安全性。

## 最终报告 / PPT 的准确描述（英文）

Reagently uses InnoDB transactions with lot-level `SELECT ... FOR UPDATE` to serialize changes affecting a shared reagent lot. A reservation is created atomically with its corresponding history record and, if stock is insufficient, an order request for the remainder. A usage submission locks the same lot, checks that the reservation belongs to the user and is still ACTIVE, marks it COMPLETED, decreases physical stock by the actual usage amount, and logs the negative physical stock change in the same transaction. The unconsumed reserved quantity is released automatically by excluding the completed reservation from the sum of ACTIVE reservations. Cancellation follows the same locking order without reducing physical stock. If any database operation fails, the transaction is rolled back. Concurrency correctness still requires verification with simultaneous MySQL test clients before being presented as a tested outcome.
