---
paths:
  - 'database/migrations/**, addons/*/database/migrations/**, tests/**'
---

# Migrations

## category 迁移执行版已入主仓库，测试禁止再手工引导 stub
category 包迁移的执行版已发布到主仓库 database/migrations（2026_05_26_135205_create_sn_categories/category_types，排在 posts 的 FK 之前——sn_category_post 外键要求 category 表先建）。测试端约定随之改变：RefreshDatabase 已从主仓库建 category 表，**禁止**在测试 beforeEach 里手工 require addons 的 stub 迁移（会 "table already exists"）——旧测试里「补建 category 包的表（迁移未发布到应用目录）」的引导块已全部移除，新测试不要再写。包迁移新增/变更时按 database.md 双侧同步：stub + 主仓库执行版一起改。
