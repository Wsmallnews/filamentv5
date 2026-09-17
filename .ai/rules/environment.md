---
paths: []
---

# Environment

## C:\html 是 F:\html 的 junction；npm run build 已自动处理路径形态，无需关心从哪个盘符跑
C 盘空间不足，`C:\html` 是 `F:\html` 的 junction（`mklink /J`），两路径同一物理目录。目录若再迁移，注意两件事：

- **vite build 的 manifest 绝对路径问题已由 `scripts/build.mjs` 自动化**（build 命令先 chdir 到 realpath 再构建）。若重建 package.json 丢失该包装，症状是整站 500（ViteException: Unable to locate file in Vite manifest，manifest key 变成 F:/ 绝对路径）——恢复 `"build": "node scripts/build.mjs"` 即可，或临时从 F: 真实路径手动构建。换 mklink /D symlink 无效（Node realpath 同样穿透）。
- **vendor/wsmallnews 的 path 仓库软链可能随盘符迁移/重挂断裂**（目录变空，autoload require 报 F: 路径失败）——跑一次 `composer install` 重建。
