---
paths:
  - 'lang/**'
---

# Lang

## 验证消息语言归主仓库 lang/，addons 包不引 laravel-lang
Laravel 标准验证消息（validation.*）归主仓库 lang/zh_CN/（validation/auth/pagination/passwords 四文件，译文取自 laravel-lang/lang 项目 zh_CN 数据，零依赖直接提交）。不要在 support 等 addons 包提供全局 validation 翻译或引入 laravel-lang 依赖：validation.* 是应用全局命名空间，包注入会污染任意宿主的语言策略；laravel-lang/common 是应用级工具链（且 2026-05 遭供应链攻击，700+ tag 投毒）。包内字段文案走包自身 sn-xxx:: 命名空间。未收录 key 自动 fallback 英文。
