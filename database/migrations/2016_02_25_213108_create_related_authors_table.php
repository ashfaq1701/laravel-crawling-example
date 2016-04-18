<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRelatedAuthorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
    	Schema::create('related_authors', function(Blueprint $table) {
    		$table->increments('id');
    		$table->integer('author_1_id')->nullable()->unsigned();
    		$table->integer('author_2_id')->nullable()->unsigned();
    		
    		$table->foreign('author_1_id')->references('id')->on('authors');
    		$table->foreign('author_2_id')->references('id')->on('authors');
    	});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('related_authors');
    }
}
