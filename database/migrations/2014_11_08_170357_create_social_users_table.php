<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSocialUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('social_users', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('ID');
            $table->string('open_id',64)->comment('OpenID');
            $table->string('provider', 32)->comment('供应商');
            $table->string('union_id')->index()->nullable()->comment('UnionID');
            $table->unsignedBigInteger('user_id')->nullable()->comment('UserID');
            $table->string('name')->nullable()->comment('姓名');
            $table->string('nickname')->nullable()->comment('昵称');
            $table->string('email')->nullable()->comment('邮箱');
            $table->string('avatar')->nullable()->comment('头像');
            $table->string('access_token')->nullable()->comment('访问令牌');
            $table->string('refresh_token')->nullable()->comment('刷新令牌');
            $table->json('data')->nullable()->comment('原始数据');
            $table->timestamp('expired_at')->nullable()->comment('过期时间');
            $table->timestamps();

            $table->unique(['provider', 'open_id']);

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('social_users');
    }
}
