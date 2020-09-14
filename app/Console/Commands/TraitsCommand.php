<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TraitsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:traits {path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a traits files';

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
        $path = $this->option('path');

        $this->info('The traits files was successfully generated');
    }
}
