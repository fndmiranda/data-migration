<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDependencyPermissionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dependency_permission', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('dependent_id')->unsigned();
            $table->foreign('dependent_id')->references('id')->on('permissions')->onDelete('cascade');
            $table->bigInteger('dependency_id')->unsigned();
            $table->foreign('dependency_id')->references('id')->on('permissions')->onDelete('cascade');
            $table->string('pivot1')->nullable();
            $table->string('pivot2')->nullable();
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
        Schema::dropIfExists('dependency_permission');
    }
}
