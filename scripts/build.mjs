/**
 * vite build 包装：junction 场景下统一切换到真实路径再构建。
 *
 * 本机 C:\html 是 F:\html 的 junction（mklink /J）。rolldown-vite 在 C: 形态的 cwd 下
 * 会把 realpath（F:）解析成绝对路径写进 manifest key（如 F:/html/.../resources/css/app.css），
 * Laravel Vite 按相对 key 查找失败导致整站 500。先 chdir 到真实路径即可让 manifest key
 * 保持相对形态——无论从哪个盘符形态执行 npm run build 都正确。
 *
 * 直接用当前 node 跑 vite 的 JS 入口（不 spawn npx.cmd——新版 Node 出于安全禁止 spawn .cmd/.bat）。
 */
import { realpathSync, existsSync } from 'node:fs';
import { chdir, cwd, execPath } from 'node:process';
import { execFileSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';

chdir(realpathSync(cwd()));

const viteBin = fileURLToPath(new URL('../node_modules/vite/bin/vite.js', import.meta.url));

if (! existsSync(viteBin)) {
    console.error(`vite 入口不存在: ${viteBin}`);
    process.exit(1);
}

execFileSync(execPath, [viteBin, 'build', ...process.argv.slice(2)], { stdio: 'inherit' });
