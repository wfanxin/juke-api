<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;


class Test extends Command
{
    protected $signature = 'Test';

    protected $description = 'Test测试';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {

    }
}
