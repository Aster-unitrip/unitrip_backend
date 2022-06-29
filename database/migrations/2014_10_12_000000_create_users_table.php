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
            $table->string('address_city')->comment('城市');
            $table->string('address_town')->comment('區域');
            $table->string('address')->comment('地址');
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
        DB::table('users')->insert(
            [
                [
                    'contact_name' => 'parker',
                    'contact_tel' => '02-1111111',
                    'role_id' => "[1, 2]",
                    'email' => 'parker@gmail.com',
                    'email_verified_at' => null,
                    'password' => '$2y$10$rEqaE6/FR6ch3hXpOITUouB3pn03xFC96lM96Vbk7s8lpnxrB0Ju.',
                    'remember_token' => null,
                    'address_city' => '台北市',
                    'address_town' => '中正區',
                    'address' => '重慶南路一段1號',
                    'company_id' => 2,
                    "updated_at" => date("Y-m-d H:i:s"),
                    "created_at" => date("Y-m-d H:i:s")
                ],[
                    'contact_name' => 'jin',
                    'contact_tel' => '02-22222222',
                    'role_id' => "[1, 2]",
                    'email' => 'jin@gmail.com',
                    'email_verified_at' => null,
                    'password' => '$2y$10$rEqaE6/FR6ch3hXpOITUouB3pn03xFC96lM96Vbk7s8lpnxrB0Ju.',
                    'remember_token' => null,
                    'address_city' => '台北市',
                    'address_town' => '中正區',
                    'address' => '重慶南路二段2號',
                    'company_id' => 2,
                    "updated_at" => date("Y-m-d H:i:s"),
                    "created_at" => date("Y-m-d H:i:s")
                ],[
                    'contact_name' => 'aster',
                    'contact_tel' => '02-22222222',
                    'role_id' => "[1, 2]",
                    'email' => 'aster@unitrip.asia',
                    'email_verified_at' => null,
                    'password' => '$2y$10$rEqaE6/FR6ch3hXpOITUouB3pn03xFC96lM96Vbk7s8lpnxrB0Ju.',
                    'remember_token' => null,
                    'address_city' => '台北市',
                    'address_town' => '中正區',
                    'address' => '重慶南路二段2號',
                    'company_id' => 2,
                    "updated_at" => date("Y-m-d H:i:s"),
                    "created_at" => date("Y-m-d H:i:s")
                ],[
                    'contact_name' => 'coda',
                    'contact_tel' => '02-22222222',
                    'role_id' => "[1, 2]",
                    'email' => 'coda@unitrip.asia',
                    'email_verified_at' => null,
                    'password' => '$2y$10$rEqaE6/FR6ch3hXpOITUouB3pn03xFC96lM96Vbk7s8lpnxrB0Ju.',
                    'remember_token' => null,
                    'address_city' => '台北市',
                    'address_town' => '中正區',
                    'address' => '重慶南路二段2號',
                    'company_id' => 2,
                    "updated_at" => date("Y-m-d H:i:s"),
                    "created_at" => date("Y-m-d H:i:s")
                ],[
                    'contact_name' => 'daisy',
                    'contact_tel' => '02-22222222',
                    'role_id' => "[1, 2]",
                    'email' => 'daisy@unitrip.asia',
                    'email_verified_at' => null,
                    'password' => '$2y$10$rEqaE6/FR6ch3hXpOITUouB3pn03xFC96lM96Vbk7s8lpnxrB0Ju.',
                    'remember_token' => null,
                    'address_city' => '台北市',
                    'address_town' => '中正區',
                    'address' => '重慶南路二段2號',
                    'company_id' => 2,
                    "updated_at" => date("Y-m-d H:i:s"),
                    "created_at" => date("Y-m-d H:i:s")
                ],[
                    'contact_name' => 'gina',
                    'contact_tel' => '02-22222222',
                    'role_id' => "[1, 2]",
                    'email' => '4a71c058@stust.edu.tw',
                    'email_verified_at' => null,
                    'password' => '$2y$10$rEqaE6/FR6ch3hXpOITUouB3pn03xFC96lM96Vbk7s8lpnxrB0Ju.',
                    'remember_token' => null,
                    'address_city' => '台北市',
                    'address_town' => '中正區',
                    'address' => '重慶南路二段2號',
                    'company_id' => 2,
                    "updated_at" => date("Y-m-d H:i:s"),
                    "created_at" => date("Y-m-d H:i:s")
                ],
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
