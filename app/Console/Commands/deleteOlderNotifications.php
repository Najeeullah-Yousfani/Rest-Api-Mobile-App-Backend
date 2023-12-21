<?php

namespace App\Console\Commands;

use App\Http\Controllers\NotificationController;
use Illuminate\Console\Command;

class deleteOlderNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:delete-older-posts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'this command is used to call notification function to delete older notification';

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
        \Log::info("Cron is working fine (running delete-older-notifications-cron) !");
        $notificationObj    =   new NotificationController();
        $notificationObj->deleteOldNotifications();
    }
}
