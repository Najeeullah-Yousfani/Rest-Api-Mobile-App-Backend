<?php

namespace App\Console\Commands;

use App\Http\Controllers\SuspiciousActivityController;
use Illuminate\Console\Command;

class checkSuspiciousActivities extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:suspicious-activities';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command is used to run on cron to check suspicious activities every 24 hours';

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
        \Log::info("Cron is working fine (running suspicious-activities-cron) !");
        $postObj    =   new SuspiciousActivityController();
        $postObj->addSuspiciousActivities();
    }
}
