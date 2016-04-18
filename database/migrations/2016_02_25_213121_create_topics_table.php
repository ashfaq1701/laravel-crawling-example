<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTopicsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
    	Schema::create('topics', function(Blueprint $table) {
    		$table->increments('id');
    		$table->string('topic', 100);
    		$table->string('url', 200);
    		$table->dateTime('date_last_crawled')->nullable();
    		$table->tinyInteger('is_active')->default(1);
    		$table->integer('site_id')->nullable()->unsigned();
    		$table->nullableTimestamps();
    		
    		$table->foreign('site_id')->references('id')->on('sites');
    	});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('topics');
    }
}
