<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCompaniesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->integer('company_type')->comment('1:供應商 2:旅行社');
            $table->string('title', 20);
            $table->string('tax_id', 12)->unique()->comment('統編');
            $table->string('tel', 15);
            $table->string('address_city', 5);
            $table->string('address_town', 5);
            $table->string('address', 30);
            $table->string('logo_path', 100);
            $table->string('website', 150);
            $table->string('owner', 10);
            $table->text('intro');
            $table->string('bank_name', 20);
            $table->string('bank_code', 5);
            $table->string('account_name', 10);
            $table->string('account_number', 20);
            $table->string('ta_register_num', 6)->nullable()->comment('旅遊業註冊編號');
            $table->string('ta_category', 2)->nullable()->comment('旅遊業類別，only in 綜合、甲種、乙種');
            $table->integer('parent_id')->nullable();
            $table->timestamps();
        });

        DB::table('companies')->insert(
            [
                [ 
                    "company_type" => '1',
                    "title" => "樂多",
                    "tax_id" => "99999999",
                    "tel" => "02-111111111",
                    "address_city" => "台北市",
                    "address_town" => "萬華區",
                    "address" => "林森路一段1號",
                    "logo_path" => "https://cdn.unitrip",
                    "website" => "https://unitrip.asia",
                    "owner" => "小馬",
                    "intro" => "zzzzzzzzzzzzzzzzzzzzzzzzzzzz",
                    "bank_name" => "國泰",
                    "bank_code" => "013",
                    "account_name" => "小馬",
                    "account_number" => "99999999999",
                    'ta_register_num' => '888888',
                    'ta_category' => '綜合',
                    "parent_id" => null,
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
        Schema::dropIfExists('companies');
    }
}
