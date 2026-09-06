<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('用户模块页头的 container 内容层带防贴边留白 sn-page-x', function () {
    $this->get('/user/login')
        ->assertOk()
        ->assertSee('container mx-auto sn-page-x', false)
        // 登录窄列内边距走卡片令牌类（lg 自动 4→6）
        ->assertSee('md:w-96 sn-padded', false)
        // 表单与底部链接的纵向留白走令牌类（lg 自动 4→6）
        ->assertSee('flex flex-col sn-mt', false)
        ->assertDontSee('md:w-96 p-4', false);
});
