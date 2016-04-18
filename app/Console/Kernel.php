<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        // Commands\Inspire::class,
        Commands\NewAuthorCommand::class,
    	Commands\PopularAuthorCommand::class,
    	Commands\PopularTopicCommand::class,
    	Commands\KeywordRangeCommand::class,
    	Commands\AuthorCommand::class,
    	Commands\TopicCommand::class,
    	Commands\GetKeywordCommand::class,
    	Commands\KeywordCommand::class,
    	Commands\ParseQuoteCommand::class,
    	Commands\AllCommand::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')
        //          ->hourly();
    }
}
