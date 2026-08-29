# Git 提交 Addons 扩展包计划

## 概述
将 addons 目录下所有未提交的扩展包提交到 git。

## 待提交的扩展包列表

根据 git status 分析，以下是所有需要提交的扩展包：

### 1. 子模块有修改的扩展包（6个）

| 扩展包路径 | 状态 | 修改内容 |
|-----------|------|---------|
| `addons/category` | 已修改 (M) | `resources/boost/guidelines/core.blade.php` - 已修改未暂存 |
| `addons/cms` | 已修改 (M) | `resources/boost/guidelines/core.blade.php` - 已修改未暂存 |
| `addons/comment` | 已修改 (M) | `resources/boost/guidelines/core.blade.php` - 已修改未暂存 |
| `addons/member` | 已修改 (M) | `resources/boost/guidelines/core.blade.php` - 已添加暂存 (A) |
| `addons/support` | 已修改 (M) | `resources/boost/guidelines/core.blade.php` - 已修改未暂存 |
| `addons/user` | 已修改 (M) | `resources/boost/guidelines/` - 未跟踪的新目录 (??) |

### 2. 全新的未跟踪扩展包（1个）

| 扩展包路径 | 状态 | 说明 |
|-----------|------|------|
| `addons/product/` | 未跟踪 (??) | 全新的扩展包目录 |

## 提交步骤

### 步骤 1：在各子模块内提交更改

对每个有修改的子模块执行以下操作（除了 addons/product 外）：

```bash
# 进入子模块目录
cd addons/{module_name}

# 添加所有更改
git add .

# 提交
git commit -m "更新 laravel-boost 的 core.blade.php 文件"

# 返回根目录
cd ../..
```

### 步骤 2：提交 addons/product 新扩展包

```bash
cd addons/product
git init  # 如果还没有初始化 git
git add .
git commit -m "初始化 product 扩展包"
cd ../..
```

### 步骤 3：在主仓库更新子模块引用

```bash
# 在主仓库中添加所有子模块的更新
git add addons/category addons/cms addons/comment addons/member addons/support addons/user

# 如果 product 是新的子模块，需要添加
git add addons/product

# 提交主仓库的子模块引用更新
git commit -m "更新 laravel-boost 的 core.blade.php 文件"
```

## 风险提示

1. **addons/product** 是新的未跟踪目录，需要确认是否需要作为子模块添加或直接提交
2. 部分子模块的文件状态不同（有的已暂存，有的未暂存），需要逐个处理
3. 需要确认各子模块是否有远程仓库配置，以便推送到远程

## 需要确认的问题

1. addons/product 是否需要添加为 git 子模块？还是直接作为普通目录提交？
2. 提交后是否需要推送到远程仓库？