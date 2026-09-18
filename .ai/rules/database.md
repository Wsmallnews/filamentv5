# 数据库迁移规则

## 无兼容包袱（长期有效的前提）

发布的包目前没有真实用户在用，数据库结构变更**不背负迁移兼容包袱**。

## 删字段 / 改结构：直接编辑原迁移，禁止叠加增量迁移

- 删除字段（如 navigation 的 slug）、改列定义：直接在**原始建表迁移**中移除/修改，不新建 `remove_xxx_column` 类增量迁移。
- 双侧同步：`addons/<包>/database/migrations/*.php.stub` 与主仓库 `database/migrations/` 里的执行版必须同步修改。
- 改完后 `php artisan migrate:fresh --seed` 重建验证（开发库数据可弃）。

## 不做存量数据迁移脚本

结构收敛（如导航 content 通道 → Page 实体）不写数据搬迁迁移脚本；演示/开发数据重新 seed。

## team_id 字段 → 模型必须加 team() 关联

凡表带 `team_id` 字段，对应模型**必须**加 `team()` BelongsTo 关联（多租户查询与预加载依赖）：

```php
public function team(): BelongsTo
{
    return $this->belongsTo(Utils::getTenantModel());
}
```

新建迁移含 `team_id` 时，同步在模型加好关联再收工。
