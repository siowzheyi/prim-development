<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserExpensesItemTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_expenses_item', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            
            $table->string('status')->nullable();
            
            $table->bigInteger('expenses_id')->unsigned();
            $table->foreign('expenses_id')->references('id')->on('expenses')->onDelete('cascade');
            $table->bigInteger('user_expenses_id')->unsigned();
            $table->foreign('user_expenses_id')->references('id')->on('user_expenses')->onDelete('cascade');
            
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_expenses_item');
    }
}
