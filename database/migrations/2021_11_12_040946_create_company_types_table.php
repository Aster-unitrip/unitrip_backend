<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCompanyTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('company_types', function (Blueprint $table) {
            $table->id();
            $table->string('type_name')->comment('公司類型名稱');
            $table->timestamps();
        });
        DB::table('company_types')->insert(
            [
                [ 'type_name' => '供應商'],
                [ 'type_name' => '旅行社'],
                [ 'type_name' => '車行'],
                [ 'type_name' => '飯店'],
                [ 'type_name' => '餐廳'],
                [ 'type_name' => '體驗'],
                [ 'type_name' => '其他'],
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
        Schema::dropIfExists('company_types');
    }
}
