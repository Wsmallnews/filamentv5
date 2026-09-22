---
paths:
  - 'addons/filament-nestedset/src/**'
---

# Filament Nestedset Src

## filament-nestedset 保持独立：不依赖 support，原生安装命令，planner 自动跳过
filament-nestedset 是独立 UI 工具包（树形管理基类），**不依赖 support 包、也不允许引入 support 依赖**——它是被 support 生态其他包（cms/category）依赖的更底层包，引入会造成循环依赖。因此：不用 ModuleRegistry 登记、不用 PackageInstallCommand 基类（基类在 support 里）、保留原生 spatie hasInstallCommand 安装命令。InstallPlanner 的依赖编排会自动跳过它（installCommandOf 只纳入 PackageInstallCommand 子类实例，原生命令的包不进计划）——其 config 发布由用户按需单独执行 sn-filament-nestedset:install。新包判断归属：被多个领域包共同依赖的底层 UI/工具能力 → 参考 nestedset 保持独立；领域功能包 → 依赖 support 走统一安装体系。
