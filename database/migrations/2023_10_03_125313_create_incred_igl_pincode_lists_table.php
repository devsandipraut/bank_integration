<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIncredIglPincodeListsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('incred_igl_pincode_lists', function (Blueprint $table) {
            $table->id();
            $table->string('pincode');
            $table->string('state');
            $table->string('district');
            $table->string('city');
            $table->string('tagging');
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
        Schema::dropIfExists('incred_igl_pincode_lists');
    }
}
