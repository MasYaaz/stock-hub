<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateStockDataTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'emiten_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            // --- DATA HARGA (Volatile - Diupdate via Yahoo Finance) ---
            'last_price' => [
                'type' => 'DECIMAL',
                'constraint' => '15,2',
                'default' => 0.00,
            ],
            'previous_close' => [
                'type' => 'DECIMAL',
                'constraint' => '15,2',
                'default' => 0.00,
            ],
            'day_high' => [
                'type' => 'DECIMAL',
                'constraint' => '15,2',
                'default' => 0.00,
            ],
            'day_low' => [
                'type' => 'DECIMAL',
                'constraint' => '15,2',
                'default' => 0.00,
            ],
            // --- DATA FUNDAMENTAL (Semi-Statis - Diupdate via FMP Stable) ---
            'market_cap' => [
                'type' => 'DECIMAL',
                'constraint' => '20,2',
                'null' => true,
            ],
            'pbv' => [
                'type' => 'DECIMAL',
                'constraint' => '5,2',
                'null' => true,
                'comment' => 'Price to Book Value Ratio',
            ],
            'per' => [
                'type' => 'DECIMAL',
                'constraint' => '5,2',
                'null' => true,
                'comment' => 'Price to Book Value Ratio',
            ],
            'roe' => [
                'type' => 'DECIMAL',
                'constraint' => '5,2',
                'null' => true,
                'comment' => 'Return on Equity in Percentage',
            ],
            'der' => [
                'type' => 'DECIMAL',
                'constraint' => '5,2',
                'null' => true,
                'comment' => 'Debt to Equity Ratio',
            ],
            'dividend_yield' => [
                'type' => 'DECIMAL',
                'constraint' => '5,2',
                'null' => true,
                'comment' => 'Dividend Yield in Percentage',
            ],
            'dividend' => [
                'type' => 'DECIMAL',
                'constraint' => '15,2',
                'null' => true,
                'comment' => 'Dividend per share in nominal value (IDR)',
            ],
            'beta' => [
                'type' => 'DECIMAL',
                'constraint' => '5,3',
                'null' => true,
                'comment' => 'Volatility metric from FMP',
            ],
            'employees' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            // --- PENANDA UPDATE (Smart Fetcher) ---
            'price_updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => 'Update terakhir data harga/chart'
            ],
            'fundamental_updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => 'Update terakhir data fundamental/FMP'
            ],
        ]);

        $this->forge->addKey('id', true);

        // Foreign Key ke tabel emiten
        $this->forge->addForeignKey('emiten_id', 'emiten', 'id', 'CASCADE', 'CASCADE');

        $this->forge->createTable('stock_data');
    }

    public function down()
    {
        $this->forge->dropTable('stock_data');
    }
}