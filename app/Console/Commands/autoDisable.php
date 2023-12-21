<?php

namespace App\Console\Commands;

use App\Http\Controllers\PostController;
use Illuminate\Console\Command;

class autoDisable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:auto-disable';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command is used to call post method to disable post in effect of algorithm';

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
        \Log::info("Cron is working fine (running auto-disable-cron) !");
        $postObj    =   new PostController();
        $postObj->autoDisable();
    }
}
