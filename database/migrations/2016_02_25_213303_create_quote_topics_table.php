<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQuoteTopicsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
    	Schema::create('quote_topics', function(Blueprint $table) {
    		$table->increments('id');
    		$table->integer('id_quote')->nullable()->unsigned();
    		$table->integer('id_topic')->nullable()->unsigned();
    		$table->tinyInteger('is_active')->default(1);
    		$table->nullableTimestamps();
    		$table->foreign('id_quote')->references('id')->on('quotes');
    		$table->foreign('id_topic')->references('id')->on('topics');
    	});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('quote_topics');
    }
}
