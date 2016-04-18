<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Author;
use App\Models\Topic;
use App\Models\Quote;
use App\Models\Site;
use Yangqi\Htmldom\Htmldom;
use Carbon\Carbon;
use DB;
use Exception;

class TopicCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crawl:topic {--topic_url=} {--topic_name=} {--cleanup}';

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
    		DB::table('topics')->update(array('date_last_crawled' => null));
    		DB::statement('TRUNCATE quote_topics');
    		DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    	}
    	if(!empty($options['topic_url']))
    	{
    		$topics = Topic::where('url', $options['topic_url'])->get();
    		foreach ($topics as $topic)
    		{
    			while(true)
    			{
    				try 
    				{
    					if(!empty($topic->date_last_crawled))
    					{
    						$this->info('Quotes for topic ' . $topic->topic . ' already exists in the system');
    						break;
    					}
    					$this->crawlTopic($topic->url, $topic);
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
    	else if(!empty($options['topic_name']))
    	{
    		$topics = Topic::where('topic', $options['topic_name'])->get();
    		foreach ($topics as $topic)
    		{
    			while(true)
    			{
    				try 
    				{
    					if(!empty($topic->date_last_crawled))
    					{
    						$this->info('Quotes for topic ' . $topic->topic . ' already exists in the system');
    						break;
    					}
    					$this->crawlTopic($topic->url, $topic);
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
    	else
    	{
    		while(true)
    		{
    			$topics = Topic::whereNull('date_last_crawled')->limit(20)->get();
    			if($topics->count() == 0)
    			{
    				break;
    			}
    			foreach($topics as $topic)
    			{
    				while (true)
    				{
    					try 
    					{
    						$this->crawlTopic($topic->url, $topic);
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
    }
    
    public function crawlTopic($url, $topic)
    {
    	$url = 'http://www.brainyquote.com'.$url;
    	$html = new Htmldom($url);
    	$paginationUl = $html->find('ul.pagination', 0);
    	$topic = $this->crawlTopicPage($html, $topic, 1);
    	if(!empty($paginationUl))
    	{
    		$paginationLis = $paginationUl->find('li');
    		$lastPageElement = $paginationLis[count($paginationLis)-2];
    		$lastPage = $lastPageElement->plaintext;
    		for($i = 2; $i <= $lastPage; $i++)
    		{
    			$currentUrl = $url . $i;
    			$currentHtml = new Htmldom($currentUrl);
    			$topic = $this->crawlTopicPage($currentHtml, $topic, $i);
    		}
    	}
    	$topic->date_last_crawled = Carbon::now();
    	$topic->save();	
    }
    
    public function crawlTopicPage($html, $topic, $page)
    {
    	$site = Site::where('site', 'http://www.brainyquote.com/')->first();
    	$quotesTopContainer = $html->find('#quotesList', 0);
    	if(!empty($quotesTopContainer))
    	{
    		$quotesContainers = $quotesTopContainer->find('div.boxyPaddingBig');
    		foreach ($quotesContainers as $quoteContainer)
    		{
    			$quoteSpan = $quoteContainer->find('span.bqQuoteLink', 0);
    			$authorDiv = $quoteContainer->find('div.bq-aut', 0);
    			$quoteLink = $quoteSpan->find('a', 0);
    			$authorLink = $authorDiv->find('a', 0);
    			$quoteText = $quoteLink->plaintext;
    			$quoteUrl = $quoteLink->href;
    			$authorUrl = $authorLink->href;
    			
    			$fullname = trim($authorLink->plaintext);
    			 
    			$authorNameParts = explode(' ', $fullname);
    			if(count($authorNameParts) == 1)
    			{
    				$firstName = $authorNameParts[0];
    				$lastName = null;
    			}
    			else
    			{
    				$firstNameArray = array_slice($authorNameParts, 0, count($authorNameParts) - 1);
    				$firstName = implode(' ', $firstNameArray);
    				$lastName = $authorNameParts[count($authorNameParts) - 1]; 
    			}
    			
    			if(!empty($lastName))
    			{
    				$letter = strtolower($lastName[0]);
    			}
    			else
    			{
    				$letter = strtolower($firstName[0]);
    			}
    			
    			$quotes = Quote::where('url', $quoteUrl)->get();
    			if($quotes->count() == 0)
    			{
    				$authors = Author::where('url', $authorUrl)->get();
    				if($authors->count() > 0)
    				{
    					$author = $authors->first();
    				}
    				else
    				{
    					$author = new Author();
    					$author->full_name = $fullname;
    					$author->first_name = $firstName;
    					$author->last_name = $lastName;
    					$author->url = $authorUrl;
    					$author->alphabet = $letter;
    					$author->site_id = $site->id;
    					$author->save();
    				}
    				
    				$quote = new Quote();
    				$quote->quote = $quoteText;
    				$quote->author_id = $author->id;
    				$quote->url = $quoteUrl;
    				$quote->site_id = $site->id;
    				$quote->save();
    				if(!$quote->topics->find($topic->id))
    				{
    					$quote->topics()->attach($topic->id, ["created_at"=>Carbon::now()]);
    				}
    			}
    			else
    			{
    				$quote = $quotes->first();
    				if(!$quote->topics->find($topic->id))
    				{
    					$quote->topics()->attach($topic->id, ["created_at"=>Carbon::now()]);
    				}
    			}
    		}
    	}
    	$this->info('Quotes for topic ' . $topic->topic . ' and page no. ' . $page . ' has been retrieved and stored');
    	return $topic;
    }
}