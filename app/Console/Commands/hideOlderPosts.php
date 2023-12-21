<?php

namespace App\Console\Commands;

use App\Http\Controllers\PostController;
use Illuminate\Console\Command;

class hideOlderPosts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:older-posts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command is used to find older post and hide them from the app';

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
        \Log::info("Cron is working fine (running find-older-post-cron) !");
        $postObj    =   new PostController();
        $postObj->hideOlderPosts();
    }
}
