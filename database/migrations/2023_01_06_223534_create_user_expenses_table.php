<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserExpensesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_expenses', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            
            $table->string('status')->nullable();
            $table->double('total_amount', 8, 2)->nullable();

            $table->bigInteger('organization_user_student_id')->unsigned();
            $table->foreign('organization_user_student_id')->references('id')->on('organization_user_student')->onDelete('cascade');
            $table->bigInteger('transaction_id')->unsigned();
            $table->foreign('transaction_id')->references('id')->on('transactions')->onDelete('cascade');
            $table->bigInteger('payment_type_id')->unsigned();
            $table->foreign('payment_type_id')->references('id')->on('payment_type')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_expenses');
    }
}
