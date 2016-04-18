<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Author;
use Yangqi\Htmldom\Htmldom;
use DB;
use Exception;

class PopularAuthorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crawl:popular-author {--cleanup}';

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
    		DB::table('authors')->update(array('is_popular' => null));
    	}
    	while (true)
    	{
    		try 
    		{
        		$url = 'http://www.brainyquote.com/quotes/favorites.html';
        		$html = new Htmldom($url);
        		$bqlns = $html->find('div.bqLn');
        		foreach ($bqlns as $bqln)
        		{
        			$link = $bqln->find('a', 0);
        			if(!empty($link))
        			{
        				$fullname = $link->plaintext;
        				if(isset($link->href))
        				{
        					$href = $link->href;
        					$authors = Author::where('full_name', $fullname)
        									->where('url', $href)
        									->get();
        					if($authors->count() > 0)
        					{	
        						$author = $authors->first();
        						$author->is_popular = 1;
        						$author->save();
        						$this->info('Author ' . $fullname . ' marked as popular');
        					}
        				}
        			}
        		}
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
