<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\KeywordRange;
use App\Models\Site;
use Yangqi\Htmldom\Htmldom;
use DB;
use Exception;

class KeywordRangeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crawl:keyword-range {--cleanup}';

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
    		DB::statement('TRUNCATE keyword_ranges');
    		DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    	}
    	$site = Site::where('site', 'http://www.brainyquote.com/')->first();
    	while (true)
    	{
    		try 
    		{
    			$url = 'http://www.brainyquote.com/quotes/topics.html';
    			$html = new Htmldom($url);
    			$containerDiv = $html->find('div.bq_s', 0);
    			$itemContainers = $containerDiv->find('div.bqLn');
    			foreach ($itemContainers as $container)
    			{
    				$link = $container->find('a', 0);
    				$name = $link->plaintext;
    				$href = $link->href;
    				if(!empty($link))
    				{
    					$keywordRanges = DB::select("SELECT * FROM  keyword_ranges WHERE url = ?", [$href]);
    					if(count($keywordRanges) == 0)
    					{
    						$keywordRange = new KeywordRange();
    						$keywordRange->keyword_range = $name;
    						$keywordRange->url = $href;
    						$keywordRange->site_id = $site->id;
    						$keywordRange->save();
    						$this->info('Keyword range for ' . $name . ' is created');
    					}
    				}
    			}
    			$this->info('All keyword ranges created');
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
