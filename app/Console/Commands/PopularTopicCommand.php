<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Topic;
use App\Models\Site;
use Yangqi\Htmldom\Htmldom;
use Carbon\Carbon;
use DB;
use Exception;

class PopularTopicCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crawl:popular-topic {--cleanup}';

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
    		DB::statement('TRUNCATE topics');
    		DB::statement('TRUNCATE quote_topics');
    		DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    	}
    	$site = Site::where('site', 'http://www.brainyquote.com/')->first();
    	while(true)
    	{
    		try 
    		{
    			$url = 'http://www.brainyquote.com/quotes/topics.html';
    			$html = new Htmldom($url);
    			$containerDiv = $html->find('div.bq_left', 1);
    			$itemContainerDivs = $containerDiv->find('div.bqLn');
    			foreach ($itemContainerDivs as $container)
    			{
    				$link = $container->find('a', 0);
    				$name = $link->plaintext;
    				$href = $link->href;
    				if(!empty($link))
    				{
    					$topics = Topic::where('topic', $name)
    									->where('url', $href)
    									->get();
    					if($topics->count() == 0)
    					{
    						$topic = new Topic();
    						$topic->topic = $name;
    						$topic->url = $href;
    						$topic->site_id = $site->id;
    						$topic->save();
    						$this->info('Topic for ' . $name . ' is created');
    					}
    				}
    			}	
    			$this->info('All topics created');
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
