<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStudentExpensesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('student_expenses', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('status')->nullable();
            $table->bigInteger('expenses_id')->unsigned();
            $table->foreign('expenses_id')->references('id')->on('expenses')->onDelete('cascade');
            $table->bigInteger('class_student_id')->unsigned();
            $table->foreign('class_student_id')->references('id')->on('class_student')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('student_expenses');
    }
}
