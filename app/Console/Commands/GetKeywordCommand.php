<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\KeywordRange;
use App\Models\Keyword;
use App\Models\Site;
use Yangqi\Htmldom\Htmldom;
use Carbon\Carbon;
use DB;
use Exception;

class GetKeywordCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crawl:get-keyword {--cleanup}';

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
    		DB::table('keyword_ranges')->update(array('date_last_crawled' => null));
    		DB::statement('TRUNCATE keywords');
    		DB::statement('TRUNCATE quote_keywords');
    		DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    	}
    	while(true)
    	{
    		$keywordRanges = KeywordRange::whereNull('date_last_crawled')->limit(20)->get();
    		if($keywordRanges->count() == 0)
    		{
    			break;
    		}
    		foreach($keywordRanges as $keywordRange)
    		{
    			while(true)
    			{
    				try 
    				{
    					$this->crawlKeywordRange($keywordRange->url, $keywordRange);
    					$keywordRange->date_last_crawled = Carbon::now();
    					$keywordRange->save();
    					break;
    				}
    				catch(Exception $e)
    				{
    					$this->info($e->getMessage() . ' - failed. Retrying...');
    					continue;
    				}
    			}
    		}
    	}
    }
    
    public function crawlKeywordRange($url, $keywordRange)
    {
    	$site = Site::where('site', 'http://www.brainyquote.com/')->first();
    	$url = 'http://www.brainyquote.com'.$url;
    	$html = new Htmldom($url);
    	$keywordContainers = $html->find('div.bq_left > div.col-md-8 > div.container > div.bqLn');
    	$count = 0;
    	foreach ($keywordContainers as $keywordContainer)
    	{
    		$keywordLink = $keywordContainer->find('a', 0);
    		if(!empty($keywordLink))
    		{
    			$keywordText = $keywordLink->plaintext;
    			$keywordHref = $keywordLink->href;
    			$keywords = Keyword::where('url', $keywordHref)->get();
    			if($keywords->count() == 0)
    			{
    				$keyword = new Keyword();
    				$keyword->keyword = $keywordText;
    				$keyword->url = $keywordHref;
    				$keyword->keyword_range_id = $keywordRange->id;
    				$keyword->site_id = $site->id;
    				$keyword->save();
    				$count++;
    			}
    		}
    	}
    	if($count == 0)
    	{
    		$this->info('Keyword range ' . $keywordRange->keyword_range . ' already fully present is system');
    	}
    	else
    	{
    		$this->info('Keyword range ' . $keywordRange->keyword_range . ' crawled successfully');
    	}
    }
}
