<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GetRockOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mycoin:getrockorders {fund_id : the instrument (LTCEUR, BTCEUR...)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List Your Order';

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
        $fund_id = $this->argument('fund_id');
        $result = \App\RockApi::orders($fund_id);
        $headers=["id",'fund_id','side','price', 'amount', 'status'];
        $this->table($headers, $result["orders"]);   
    }


}
