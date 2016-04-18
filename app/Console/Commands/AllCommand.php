<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;

class AllCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crawl:all {--cleanup}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
    	$options = $this->option();
    	if($options['cleanup'] == true)
    	{
    		DB::statement('SET FOREIGN_KEY_CHECKS=0;');
    		DB::statement('TRUNCATE authors');
    		DB::statement('TRUNCATE keywords');
    		DB::statement('TRUNCATE keyword_ranges');
    		DB::statement('TRUNCATE nationalities');
    		DB::statement('TRUNCATE professions');
    		DB::statement('TRUNCATE quotes');
    		DB::statement('TRUNCATE quote_keywords');
    		DB::statement('TRUNCATE quote_topics');
    		DB::statement('TRUNCATE related_authors');
    		DB::statement('TRUNCATE topics');
    		DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    	}
        $this->call('crawl:new-author');
        $this->call('crawl:popular-author');
        $this->call('crawl:author');
        $this->call('crawl:popular-topic');
        $this->call('crawl:keyword-range');
        $this->call('crawl:topic');
        $this->call('crawl:get-keyword');
        $this->call('crawl:keyword');
        $this->call('crawl:parse-quote');
    }
}
