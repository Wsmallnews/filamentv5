---
paths:
  - 'addons/*/src/**'
---

# Addons Src

## Eloquent 查询链顺序：无参 scope → 带参 scope → with → where → 终结方法
Eloquent 查询链统一顺序：① 无参数的模型 scope（normal()/published()/hidden()/ordered() 等）最前；② 带参数的 scope（snScope()/scopeable()/自定义带参 scope）其次；③ with()/withDepth() 等预加载修饰；④ where()/whereHas() 条件；⑤ 终结方法（first()/find()/get()/paginate()）。例：Model::query()->normal()->snScope(...$scopeable)->where('type', $type)->first();
