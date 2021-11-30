<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('contact_name')->comment('聯絡人姓名');
            $table->string('contact_tel')->comment('聯絡人電話');
            $table->json('role_id')->comment('權限角色類型，目前（20211112）用不到');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->integer('company_id')->comment('所屬公司');
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
        DB::table('users')->insert(
            [
                [ 'contact_name' => 'parker', 'contact_tel' => '02-1111111', 'role_id' => "[1, 2]", 'email' => 'parker@gmail.com', 'email_verified_at' => null, 'password' => '$2y$10$rEqaE6/FR6ch3hXpOITUouB3pn03xFC96lM96Vbk7s8lpnxrB0Ju.', 'remember_token' => null, 'company_id' => 1],
            ]
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
