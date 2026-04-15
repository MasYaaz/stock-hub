<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Libraries\StockFetcher;

class StockSync extends BaseCommand
{
    protected $group = 'Stock';
    protected $name = 'stock:sync';
    protected $description = 'Menjalankan fetcher saham terus menerus di background.';

    public function run(array $params)
    {
        $fetcher = new StockFetcher();
        CLI::write('Background Sync Started...', 'green');

        while (true) {
            $result = $fetcher->fetchStepByStep(200);
            CLI::write('[' . date('H:i:s') . '] ' . $result);

            // Jeda 30 detik agar tidak membebani CPU Ryzen 5500 kamu
            // dan tidak kena ban oleh Yahoo Finance
            sleep(30);
        }
    }
}