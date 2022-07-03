<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeFieldsCompanyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('ta_register_num', 6)->nullable()->unique()->comment('旅遊業註冊編號');
            $table->string('tqaa_num', 5)->after('ta_category')->nullable()->unique()->comment('品保協會編號');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('ta_register_num');
            $table->dropColumn('tqaa_num');
        });
    }
}
