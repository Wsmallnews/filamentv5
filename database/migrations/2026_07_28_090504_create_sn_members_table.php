<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sn_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->default(0)->comment('用户ID');
            $table->unsignedBigInteger('team_id')->nullable()->comment('团队ID');
            $table->json('counter')->nullable()->comment('计数器');
            $table->string('status', 20)->comment('状态');
            $table->json('options')->nullable()->comment('选项');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['user_id', 'team_id'], 'sn_members_user_team_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sn_members');
    }
};
