---
paths:
  - 'addons/cms/src/Models/**'
---

# Models

## is_home 互斥限定在同一导航树（scope + type_id）内
Navigation is_home 互斥范围 = scope_type + scope_id + type_id（Navigation::booted 的 saving 钩子）：同 scope 下每棵导航树（不同 type_id）各自持有首页标记互不干扰；footer 等派生 scope 天然隔离，无需额外条件。改动互斥范围前先核对该语义。
