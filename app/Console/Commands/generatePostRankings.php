<?php

namespace App\Console\Commands;

use App\Http\Controllers\PostController;
use Illuminate\Console\Command;
use SebastianBergmann\Environment\Console;

class generatePostRankings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'post:rankings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command is used in task sceduler to generate post rankings every 20 minutes';

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
     * @return int
     */
    public function handle()
    {
        \Log::info("Cron is working fine!");
        $postObj    =   new PostController();
        $postObj->updateRankings();
    }
}
