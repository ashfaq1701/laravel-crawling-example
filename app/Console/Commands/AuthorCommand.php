<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Author;
use App\Models\Nationality;
use App\Models\Profession;
use App\Models\Quote;
use App\Models\Site;
use PHPHtmlParser\Dom;
use Carbon\Carbon;
use DB;
use Exception;

class AuthorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crawl:author {--author_url=} {--author_name=} {--cleanup}';

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
        	DB::table('authors')->update(array('date_last_crawled' => null));
        }
        if(!empty($options['author_url']))
        {
        	$authors = Author::where('url', $options['author_url'])->get();
        	foreach ($authors as $author)
        	{
        		while(true)
        		{
        			try 
        			{
        				if(!empty($author->date_last_crawled))
        				{	
        						$this->info('Author details for ' . $author->full_name . ' already exists in the system');
        						break;
        				}
        				$this->crawlAuthor($author->url, $author);
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
        else if(!empty($options['author_name']))
        {
        	$authors = Author::where('full_name', $options['author_name'])->get();
        	foreach ($authors as $author)
        	{
        		while(true)
        		{
        			try 
        			{
        				if(!empty($author->date_last_crawled))
        				{
        					$this->info('Author details for ' . $author->full_name . ' already exists in the system');
        					break;
        				}
        				$this->crawlAuthor($author->url, $author);
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
        		$authors = Author::whereNull('date_last_crawled')->limit(20)->get();
        		if($authors->count() == 0)
        		{
        			break;
        		}
        		foreach($authors as $author)
        		{
        			while (true)
        			{
        				try 
        				{
        					$this->crawlAuthor($author->url, $author);
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
    
    public function crawlAuthor($url, $author)
    {
    	$url = 'http://www.brainyquote.com'.$url;
    	$html = new Dom;
    	$html->loadFromUrl($url);
    	$paginationUl = $html->find('ul.pagination', 0);
    	$author = $this->crawlAuthorPage($html, $author, 1);
    	if(!empty($paginationUl))
    	{
    		$paginationLis = $paginationUl->find('li');
    		$lastPageElement = $paginationLis[count($paginationLis)-2];
    		$lastPageLink = $lastPageElement->find('a', 0);
    		$lastPage = $lastPageLink->text(true);
    		for($i = 2; $i <= $lastPage; $i++)
    		{
    			$currentUrl = $url . '_' . $i;
    			$currentHtml = new Dom;
    			$currentHtml->loadFromUrl($currentUrl);
    			$author = $this->crawlAuthorPage($currentHtml, $author, $i);
    		}
    	}
    	$author->date_last_crawled = Carbon::now();
    	$author->save();
    }
    
    public function crawlAuthorPage($html, $author, $pageNo)
    {
    	$site = Site::where('site', 'http://www.brainyquote.com/')->first();
    	if($pageNo == 1)
    	{
    		$rightInfoDivs = $html->find('div.bq_fl');
    		foreach($rightInfoDivs as $currentRightInfoDiv)
    		{
    			$rightInfoDivText = $currentRightInfoDiv->text(true);
    			if(!empty(strpos($rightInfoDivText, 'Nationality:')))
    			{
    				$rightInfoDiv = $currentRightInfoDiv;
    				break;
    			}
    		}
    		if(!empty($rightInfoDiv))
    		{
    			$biographyContainers = $rightInfoDiv->find('div.bqLn');
    			foreach ($biographyContainers as $biographyContainer)
    			{
    				$text = $biographyContainer->text(true);
    				$textParts = explode(':', $text);
    				$partTitle = $textParts[0];
    				if($partTitle == 'Nationality')
    				{
    					$nationalityStr = trim($textParts[1]);
    					$nationalities = Nationality::where('nationality', $nationalityStr)->get();
    					if($nationalities->count() > 0)
    					{
    						$nationality = $nationalities->first();
    					}
    					else 
    					{
    						$nationality = new Nationality();
    						$nationality->nationality = $nationalityStr;
    						$nationality->save();
    					}
    					$author->nationality_id = $nationality->id;
    				}
    				else if($partTitle == 'Type')
    				{
    					$title = trim($textParts[1]);
    					$professions = Profession::where('profession', $title);
    					if($professions->count() > 0)
    					{
    						$profession = $professions->first();
    					}
    					else
    					{
    						$profession = new Profession();
    						$profession->profession = $title;
    						$profession->save();
    					}
    					$author->profession_id = $profession->id;
    				}
    				else if($partTitle == 'Born')
    				{
    					$born = trim($textParts[1]);
    					try 
    					{
    						try 
    						{
    							$result = Carbon::createFromFormat('F d, Y', $born);
    						}
    						catch(Exception $e)
    						{
    							try 
    							{
    								$result = Carbon::createFromFormat('F, Y', $born);
    							}
    							catch(Exception $e)
    							{
    								$resultParts = explode(' ', $born);
    								if((count($resultParts) == 1) && (strlen($born) == 4))
    								{
    									$result = Carbon::createFromDate($born, 1, 1);
    								}
    								else 
    								{
    									throw new Exception('No potential date formats matched');
    								}
    							}
    						}
    						$author->date_of_birth = $result;
    					}
    					catch(Exception $e)
    					{
    						$this->info($e->getMessage());
    						$author->date_of_birth = null;
    					}
    				}
    				else if($partTitle == 'Died')
    				{
    					$died = trim($textParts[1]);
    					try 
    					{
    						try 
    						{
    							$result = Carbon::createFromFormat('F d, Y', $died);
    						}
    						catch(Exception $e)
    						{
    							try 
    							{
    								$result = Carbon::createFromFormat('F, Y', $died);
    							}
    							catch(Exception $e)
    							{
    								$resultParts = explode(' ', $died);
    								if((count($resultParts) == 1) && (strlen($died) == 4))
    								{
    									$result = Carbon::createFromDate($died, 1, 1);
    								}
    								else 
    								{
    									throw new Exception('No potential date formats matched');
    								}
    							}
    						}
    						$author->date_of_death = $result;
    					}
    					catch(Exception $e)
    					{
    						$author->date_of_death = null;
    					}
    				}
    			}
    			$biographyDiv = $rightInfoDiv->find('div.bq_s');
    			foreach ($biographyDiv as $biography)
    			{
    				$biographyLinks = $biography->find('a');
    				foreach($biographyLinks as $biographyLink)
    				{
    					if($biographyLink->text(true) == 'Read full biography')
    					{
    						$biographyHref = $biographyLink->href;
    						$author->full_biography_link = $biographyHref;
    					}
    				}
    			}
    			$rightLinkDiv = $html->find('div.bq_fl', 1);
    			if(!empty($rightLinkDiv))
    			{
    				$links = $rightLinkDiv->find('a');
    				foreach ($links as $link)
    				{
    					$href = $link->href;
    					if (strpos($href, 'amazon') == true) {
    						$href = str_replace('tag=brainyquote-20&amp;', '', $href);
    						$author->amazon_link = $href;
    					}
    				}
    			}
    			$relatedAuthorLinks = $html->find('div.block-sm-holder > a.block-sm');
    			foreach ($relatedAuthorLinks as $relatedAuthorLink)
    			{
    				$href = $relatedAuthorLink->href;
    				$span = $relatedAuthorLink->find('span', 0);
    				$fullname = trim($span->text(true));
    			
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
    			
    				$relatedAuthors = Author::where('url', $href)->get();
    				if($relatedAuthors->count() > 0)
    				{
    					$relatedAuthor = $relatedAuthors->first();
    					if(empty($author->related_authors->find($relatedAuthor->id)))
    					{
    						$author->related_authors()->attach($relatedAuthor->id);
    					}
    				}
    				else
    				{
    					$relatedAuthor = new Author();
    					$relatedAuthor->full_name = $fullname;
    					$relatedAuthor->first_name = $firstName;
    					$relatedAuthor->last_name = $lastName;
    					$relatedAuthor->url = $href;
    					$relatedAuthor->alphabet = $letter;
    					$relatedAuthor->site_id = $site->id;
    					$relatedAuthor->save();
    				
    					$author->related_authors()->attach($relatedAuthor->id);
    				}
    			}
    		}
    		else 
    		{
    			$this->info('Required div not found. Continuing to next iteration');
    		}
    	}
    	$quoteLinks = $html->find('#quotesList span.bqQuoteLink a');
    	foreach($quoteLinks as $quoteLink)
    	{
    		$href = $quoteLink->href;
    		$quotes = Quote::where('url', $href)->get();
    		if($quotes->count() == 0)
    		{
    			$quote = new Quote();
    			$quote->quote = $quoteLink->text(true);
    			$quote->url = $href;
    			$quote->author_id = $author->id;
    			$quote->site_id = $site->id;
    			$quote->save();
    		}
    	}
    	$author->save();
    	$this->info('Author details for ' . $author->full_name . ' and page no. ' . $pageNo . ' has been retrieved and stored');
    	return $author;
    }
}
