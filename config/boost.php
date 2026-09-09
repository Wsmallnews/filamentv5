<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Laravel Boost 定制
    |--------------------------------------------------------------------------
    |
    | agents.claude_code.guidelines_path 覆盖 Claude Code agent 的规则写入目标：
    | 默认 CLAUDE.md，这里改为 AGENTS.md（本项目的工作说明文件），
    | 使 `php artisan boost:update` 直接原地更新 AGENTS.md 的
    | <laravel-boost-guidelines> 区块，标签外的手写内容不受影响。
    |
    | 根目录 boost.json 是 boost 的安装清单（agents / packages / 开关），
    | 与本文件职责不同，两者都需要保留。
    */

    'agents' => [
        'claude_code' => [
            'guidelines_path' => 'AGENTS.md',
        ],
    ],
];
