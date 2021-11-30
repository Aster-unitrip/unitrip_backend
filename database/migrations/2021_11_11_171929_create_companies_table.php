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
            $table->string('title', 20);
            $table->string('tax_id', 12)->unique()->comment('統編');;
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
            $table->integer('parent_id')->nullable();
            $table->timestamps();
        });

        DB::table('companies')->insert(
            [
                [ 
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
