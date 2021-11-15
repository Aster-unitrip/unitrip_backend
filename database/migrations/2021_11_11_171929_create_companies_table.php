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
