<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQuotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
    	Schema::create('quotes', function(Blueprint $table) {
    		$table->increments('id');
    		$table->text('quote');
    		$table->integer('author_id')->nullable()->unsigned();
    		$table->string('url', 200)->unique();
    		$table->dateTime('date_last_crawled')->nullable();
    		$table->tinyInteger('is_active')->default(1);
    		$table->integer('site_id')->nullable()->unsigned();
    		$table->nullableTimestamps();
    		$table->foreign('author_id')->references('id')->on('authors');
    		$table->unique(['url', 'author_id']);
    		
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
        Schema::drop('quotes');
    }
}
