---
paths:
  - 'addons/wallet/src/**'
---

# Wallet Src

## 钱包账本与换算铁律（team_id NULL=全局、流水不可变、快照退款）
1) team_id 语义：NULL=全局（User 跨租户共享钱包），Member 钱包取 owner 的 team_id 属性；流水行 team_id 记租户归因（current_tenant ?? wallet.team_id），绝不写 0（0 只属于 scope_id 哨兵）。2) 流水不可变：禁止 update/delete（模型 booted 抛异常），冲正走反向流水；每笔流水记 amount/balance_change/frozen_change 双增量 + balance_after/frozen_after 双快照。3) 金额一律整数最小单位（类型 decimals 自定精度，非 ISO 时不能用 MoneyCast/sn_money()->format，走 WalletType::format）。4) 幂等：WalletManager 所有变动接受 uuid（pay:{pay_sn}/refund:{refund_sn}/recharge:{id}/transfer-{out,in}:{sn}），唯一键兜底并发重放。5) 换算纪律：扣款 up 进位、退款按 PayRecord.options.wallet 快照等比例回退绝不重新换算；市场汇率缺失必须抛异常不静默。6) 类型声明归代码（Wallet::registers 懒落库，落库后以库内为准），锚定率参数归 DB（租户覆盖优先全局默认）。
