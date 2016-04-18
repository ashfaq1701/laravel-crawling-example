<?php

use Illuminate\Database\Seeder;
use Carbon\Carbon;

class SitesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
    	DB::table('sites')->delete();
    	DB::table('sites')->insert([
    		'site' => 'http://www.brainyquote.com/',
    		'created_at' => Carbon::now()
    	]);
    }
}
