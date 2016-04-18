<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Author;
use App\Models\Profession;
use App\Models\Site;
use Yangqi\Htmldom\Htmldom;
use Carbon\Carbon;
use DB;
use Exception;

class NewAuthorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crawl:new-author {--cleanup}';

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
    		DB::statement('TRUNCATE related_authors');
    		DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    	}
    	$authorsCount = Author::count();
    	if($authorsCount > 0)
    	{
    		$lower = Author::max('alphabet');
    	}
    	else 
    	{
    		$lower = 'a';
    	}
    	$letters = range($lower, 'z');
    	foreach ($letters as $letter)
    	{
    		try 
    		{
    			$url = 'http://www.brainyquote.com/authors/'.$letter;
    			while (true)
    			{
    				try 
    				{
    					$html = new Htmldom($url);
    					$this->retrieveAndStoreAuthors($html, $letter, 1);
    					$pagination = $html->find('ul.pagination', 0);
    					break;
    				}
    				catch(Exception $e)
    				{
    					$this->info($e->getMessage() . ' - failed. Retrying...');
    					continue;
    				}
    			}
    			if(!empty($pagination))
    			{
    				$lis = $pagination->find('li');
    				$lastpage = $lis[count($lis) - 2]->plaintext;
    				for($i = 2; $i <= $lastpage; $i++)
    				{
    					$currentUrl = $url.$i;
    					while (true)
    					{
    						try 
    						{
    							$currentHtml = new Htmldom($currentUrl);
    							$this->retrieveAndStoreAuthors($currentHtml, $letter);
    							break;
    						}
    						catch(Exception $e)
    						{
    							$this->info($e->getMessage() . ' - failed. Retrying...');
    							continue;
    						}
    					}
    				}
    				$this->info('Total ' . $lastpage . ' pages under letter ' . $letter);
    			}
    			$newHtml = new Htmldom($url);
    			$this->getMostPopularAuthors($newHtml, $letter);
    		}
    		catch(Exception $ex)
    		{
    			$this->info($ex->getMessage() . ' error caught. Continuing to next loop');
    			continue;
    		}
    	}
    }
    
    public function retrieveAndStoreAuthors($html, $letter)
    {	
    	$site = Site::where('site', 'http://www.brainyquote.com/')->first();
    	$authorsElement = $html->find('div.bq_s', 1);
    	if(!empty($authorsElement))
    	{
    		$authorsRows = $authorsElement->find('tr');
    		for($i = 1; $i < count($authorsRows); $i++)
    		{
    			$authorRow = $authorsRows[$i];
    			$tds = $authorRow->find('td');
    			if(!empty($tds))
    			{
    				$authorElement = $tds[0];
    				$authorLink = $authorElement->find('a', 0);
    				$authorHref = null;
    				if(!empty($authorLink))
    				{
    					$authorHref = $authorLink->href;
    				}
    				$professionElement = $tds[1];
    			
    				$professions = Profession::where('profession', $professionElement->plaintext)->get();
    				if($professions->count() > 0)
    				{
    					$profession = $professions->first();
    				}
    				else 
    				{
    					$profession = new Profession();
    					$profession->profession = $professionElement->plaintext;
    					$profession->save();
    				}
    				$fullname = $authorElement->plaintext;
    				$fullname = trim($fullname);
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
    				$authors = Author::where('url', $authorHref)->get();
    				if($authors->count() == 0)
    				{
    					$author = new Author();
    					$author->full_name = $fullname;
    					$author->first_name = $firstName;
    					$author->last_name = $lastName;
    					$author->alphabet = $letter;
    					$author->url = $authorHref;
    					$author->profession_id = $profession->id;
    					$author->site_id = $site->id;
    					$author->save();
    					$this->info($authorElement->plaintext.' - '.$authorHref.' - '.$professionElement->plaintext . ' has been stored');
    				}
    				else
    				{
    					$author = $authors->first();
    					$author->alphabet = $letter;
    					$author->save();
    					$this->info($authorElement->plaintext . ' already exists in the system');
    				}
    			}
    		}
    	}
    	else 
    	{
    		$this->info('Authors top container not found. Continuing to next iteration');
    		return;
    	}
    }
    
    public function getMostPopularAuthors($html, $letter)
    {
    	$popularTable = $html->find('table#letterPopular', 0);
    	if(!empty($popularTable))
    	{
    		$popularAs = $popularTable->find('a.block-sm-az');
    		foreach ($popularAs as $popularA)
    		{
    			$href = $popularA->href;
    			$fullname = $popularA->plaintext;
    			
    			$authors = Author::where('url', $href)->get();
    			if($authors->count() > 0)
    			{
    				$author = $authors->first();
    				$author->is_popular = 1;
    				$author->save();
    			}
    		}
    	}
    	$this->info('Most popular authors marked for letter ' . $letter);
    }
}