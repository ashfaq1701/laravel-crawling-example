<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Quote;
use App\Models\Topic;
use App\Models\Keyword;
use App\Models\Site;
use Yangqi\Htmldom\Htmldom;
use Carbon\Carbon;
use DB;
use Exception;

class ParseQuoteCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crawl:parse-quote {--cleanup}';

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
    		DB::table('quotes')->update(array('date_last_crawled' => null));
    		DB::statement('TRUNCATE quote_keywords');
    		DB::statement('TRUNCATE quote_topics');
    		DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    	}
        while(true)
        {
        	$quotes = Quote::whereNull('date_last_crawled')->limit(20)->get();
        	if($quotes->count() == 0)
        	{
        		break;
        	}
        	foreach ($quotes as $quote)
        	{
        		while(true)
        		{
        			try 
        			{
        				$this->crawlQuote($quote->url, $quote);
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
    
    public function crawlQuote($url, $quote)
    {
    	$site = Site::where('site', 'http://www.brainyquote.com/')->first();
    	$url = 'http://www.brainyquote.com'.$url;
    	$html = new Htmldom($url);
    	$keywordTopicContainerDiv = $html->find('div.bq_s > div.bq_fl', 3);
    	if(!empty($keywordTopicContainerDiv))
    	{
    		$topicKeywordLinks = $keywordTopicContainerDiv->find('a');
    		foreach ($topicKeywordLinks as $topicKeywordLink)
    		{
    			$href = $topicKeywordLink->href;
    			$name = $topicKeywordLink->plaintext;
    			if(strpos($href, 'topics') != false)
    			{
    				$topics = Topic::where('url', $href)->get();
    				if($topics->count() > 0)
    				{
    					$topic = $topics->first();
    				}
    				else
    				{
    					$topic = new Topic();
    					$topic->topic = $name;
    					$topic->url = $href;
    					$topic->site_id = $site->id;
    					$topic->save();
    				}
    				if(!$quote->topics->find($topic->id))
    				{
    					$quote->topics()->attach($topic->id, ["created_at"=>Carbon::now()]);
    				}
    			}
    			else if(strpos($href, 'keywords') != false)
    			{
    				$keywords = Keyword::where('url', $href)->get();
    				if($keywords->count() > 0)
    				{
    					$keyword = $keywords->first();
    				}
    				else
    				{
    					$keyword = new Keyword();
    					$keyword->keyword = $name;
    					$keyword->url = $href;
    					$keyword->site_id = $site->id;
    					$keyword->save();
    				}
    				if(!$quote->keywords->find($keyword->id))
    				{
    					$quote->keywords()->attach($keyword->id, ["created_at"=>Carbon::now()]);
    				}
    			}
    		}
    	}
    	$quote->date_last_crawled = Carbon::now();
    	$quote->save();
    	$this->info('Quote ' . $quote->url . ' has been parsed');
    }
}
