<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAuthorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
    	Schema::create('authors', function(Blueprint $table) {
    		$table->increments('id');
    		$table->string('full_name', 200);
    		$table->string('first_name', 150);
    		$table->string('last_name', 150)->nullable();
    		$table->char('alphabet', 1);
    		$table->string('url', 200);
    		$table->integer('profession_id')->nullable()->unsigned();
    		$table->integer('nationality_id')->nullable()->unsigned();
    		$table->date('date_of_birth')->nullable();
    		$table->date('date_of_death')->nullable();
    		$table->string('amazon_link', 200)->nullable();
    		$table->string('full_biography_link', 200)->nullable();
    		$table->dateTime('date_last_crawled')->nullable();
    		$table->tinyInteger('is_popular')->nullable();
    		$table->tinyInteger('is_active')->default(1);
    		$table->integer('site_id')->nullable()->unsigned();
    		$table->nullableTimestamps();
    		
    		$table->foreign('profession_id')->references('id')->on('professions');
    		$table->foreign('nationality_id')->references('id')->on('nationalities');
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
        Schema::drop('authors');
    }
}
