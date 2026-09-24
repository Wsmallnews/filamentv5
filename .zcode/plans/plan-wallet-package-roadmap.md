# Wallet 扩展包：首版落地与待办路线图

> 状态：首版已推送（wallet 仓库 v1 分支 `1b46bf1` + 主仓库 `00bc794`）；第二轮问答改造（自定义流水类型 / 异常提示 / get_sn / 翻译陷阱修复等）与 docs 沉淀已完成、测试全绿（wallet 31 + pay 13），**未提交**，等待审查。
> 架构详情见包内 `docs/architecture.md`（ER 图 / 六流程 / 决策速查）；账本铁律见 `.ai/rules/wallet-src.md`。

---

## 一、已完成

### 首版（已推送 v1）

- **六表结构**：`sn_wallet_types` / `sn_wallet_rates`（全局默认 + 租户覆盖）/ `sn_wallets` / `sn_wallet_transactions`（不可变）/ `sn_wallet_market_rates` / `sn_wallet_recharges`
- **WalletManager**：credit / debit / freeze / unfreeze / debitFrozen / transfer（对冲双流水）/ createRecharge / reconcile；行锁 + uuid 幂等 + 懒创建 + 禁负数
- **ConversionService**：两跳换算（订单币种 →市场汇率→ 锚定币种 →锚定率→ 钱包币种），bcmath 字符串运算，扣款 up 进位，快照全留痕
- **pay 集成**：`PayWalletOperator` 实现 `WalletOperator`（money 通道即插即用）；`Recharge` 实现 `PayableInterface`（充值闭环幂等入账）；顺手修复 pay 两处：MoneyAdapter 退款取快照 wallet_type、deduct meta 补 payable 标识（**改动在 pay 子模块工作区，未随 pay 提交**）
- **Filament 四组资源**：钱包（四类调整 Action + 流水 RM）/ 类型（锚定率 RM）/ 市场汇率 / 充值单
- **命令**：`sn-wallet:install`、`sn-wallet:reconcile`（对账，--fix 修复）
- **测试**：tests/Feature/Wallet/WalletTest.php

### 第二轮改造（未提交，待审查）

| 项 | 内容 |
|---|---|
| 自定义流水类型 | `Wallet::registerTransactionTypes()` + `TransactionTypes` 注册表 + 容错 `TransactionTypeCast` |
| 异常提示 | 用户可见异常（余额不足等）抛出点经 `__()` 翻译并含类型名，catch 后直接 getMessage() 给前端；系统类错误保持英文 |
| get_sn 掩码 | 转账单号改用发起人 id（`get_sn($from->getKey(), 'T')`），碰撞收敛到单用户并发 |
| 配置读取 | 4 处 `config('sn-wallet...')` → `Utils::getConfig()` |
| morph 规范 | 删除 4 处 getMorphClass 硬编码（enforceMorphMap 已覆盖） |
| **翻译陷阱修复** | config 里 `fn () => __('...')` 闭包在面板注册期立即求值，毒化整组翻译缓存 → 改纯字符串键；已 record-rule（`.ai/rules/config.md`） |
| 文档沉淀 | `docs/architecture.md`（Mermaid：ER / 六流程 / uuid 与 transfer_sn 分工 / 决策速查） |

---

## 二、核心设计速记（详见 docs/architecture.md）

- **流水即真相**：不可变账本，余额是快照，`sn-wallet:reconcile` 重放校验
- **两跳换算 + 快照退款**：扣款固化快照，退款按快照等比例回退，绝不重新换算；本位币可配置（默认=站点默认货币），变更不影响存量
- **uuid（唯一）= 行级幂等；transfer_sn（共享）= 转账双腿配对**
- **team_id NULL = 全局**（User 跨租户共享）；Member/Store 等自带 team_id 的 owner 挂租户，流水行记租户归因
- **类型 code 即业务命名空间**：shop_balance / cms_balance 天然隔离；门店钱包 owner=Store、用户×门店储值 owner=StoreMember
- **冻结体系**：credit(frozen) → debitFrozen（售后追回）→ unfreeze（可提现），佣金永不变负

---

## 三、待落地场景（按优先级）

### P1 业务接入（打通真实闭环）

1. **shop 收银台接入余额支付**：PayMethods 组件加 money 通道卡片（展示钱包余额/类型选择，余额不足时 catch 后直接 `$e->getMessage()`（抛出点已翻译）发通知）；下单流程传 `wallet_type`
2. **shop 声明钱包类型**：ShopServiceProvider `Wallet::registers(['shop_balance' => ..., 'point' => ...])`（含锚定率默认值）
3. **前端用户中心**：余额卡片 + 流水列表（Livewire 组件，参照 preference 包组件模式）+ 充值入口
4. **在线充值前端闭环**：充值页（选类型/金额）→ `createRecharge` → 跳 pay 支付 → 回跳结果页；PayMethods/收银台复用
5. **User 全局钱包接入**：User 模型 use `HasWallets`（或前端统一走 facade），用户中心展示跨租户共享余额

### P2 完善项

6. **提现场景**：申请提现（freeze 可用）→ 打款（pay 退款通道/debitFrozen）→ 失败解冻退回；需新增提现单模型（类似 Recharge 的 Withdraw 单）
7. **佣金分佣落地**：order 分销佣金 = `credit(frozen: true)`；确认收货 + 售后期结束触发 `unfreeze`（可挂 ScheduledTask 定时任务体系）
8. **市场汇率自动同步**：`RateResolverInterface` 外部 API 实现 + 定时刷新 `sn_wallet_market_rates`（如 exchangerate-api）
9. **充值单超时关闭**：定时任务把超时未支付 Recharge 置 Closed（RechargeStatus 已预留）
10. **对账定时化**：`sn-wallet:reconcile` 挂 schedule + 差异告警（日志/通知通道）
11. **Filament 补强**：钱包列表 owner morph 筛选（FilterComponents::morphFilter）、后台代充值（走 Recharge + pay 单）、流水导出、类型表单的租户费率覆盖真实租户选择（tenancy 启用后）
12. **多租户全面验证**：启用 `sn-support.tenant_model` 后验证钱包 scopeTenant 行为、租户费率覆盖、全局 User 钱包在租户面板的可见性策略（当前约定：租户面板只看 member 钱包）

### P3 远期预留

13. **store 模块钱包**：门店钱包 owner=Store（佣金池/分账）；门店会员储值 owner=StoreMember（store 模块建模）
14. **钱包间币种兑换**：point ↔ balance 的 swap（双 debit/credit + 双向换算快照），bavix swap 模式参考
15. **风控扩展**：单笔/日限额、异常流水冻结告警

---

## 四、已知边界与技术债

- **pay 子模块有两处未提交改动**（MoneyAdapter walletType 快照取值 + deduct meta 补 payable）：混在 pay 改造工作区，随 pay 下次提交带上；主仓库 PayTest 的 offsetUnset 用例改动同理
- **部分退款的累计舍入**：reverseBySnapshot 按比例 half_up，极端拆分下累计回退可能比原扣多 1 个最小单位（如需精确可加"累计回退 ≤ 原扣"上限校验）
- **充值单不支持部分支付入账**：全额到账才 credit（部分支付后退款由 pay 侧原路退回，钱包无感知）
- **转账无事务外单号**：transfer_sn 由 get_sn 生成（发起人掩码），同用户同秒并发随机重叠概率极低 + uuid 唯一键兜底（撞车表现为幂等重放/异常，不记错账）；对强一致要求高的调用方应自带 `options['transfer_sn']`
- **并发测试为模拟**：真实多进程并发压测未做（sqlite 测试库限制）；MySQL 上线前建议跑一轮真实并发用例
- **翻译组陷阱是通病**：其他新装包（boot 顺序晚于 Filament）同样会踩 `fn () => __()` 闭包坑，规则已记录但存量包未排查

---

## 五、如何基于本文档继续

新会话直接说「基于 .zcode/plans/plan-wallet-package-roadmap.md 继续第 N 项」即可；建议上下文顺序：

1. 本文档（全局进度）
2. `addons/wallet/docs/architecture.md`（架构与流程）
3. `.ai/rules/wallet-src.md`（账本铁律）+ `.ai/rules/addons-src.md`（金额/scope 规范）
4. 测试基准：`php artisan test tests/Feature/Wallet --compact`（当前 31 用例）

**流程约定**：改动不提交，等审查后明确指示再 commit/push（`.ai/rules/general.md`）。
