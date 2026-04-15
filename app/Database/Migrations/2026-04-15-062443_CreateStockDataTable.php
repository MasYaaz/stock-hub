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
            'last_price' => [
                'type' => 'DECIMAL',
                'constraint' => '15,2',
                'default' => 0.00,
            ],
            'pbv' => [
                'type' => 'DECIMAL',
                'constraint' => '5,2',
                'null' => true,
                'comment' => 'Price to Book Value Ratio',
            ],
            'dividend_yield' => [
                'type' => 'DECIMAL',
                'constraint' => '5,2',
                'null' => true,
                'comment' => 'Dividend Yield in Percentage',
            ],
            'market_cap' => [
                'type' => 'DECIMAL',
                'constraint' => '20,2',
                'null' => true,
            ],
            'previous_close' => [
                'type' => 'DECIMAL',
                'constraint' => '15,2',
                'after' => 'last_price'
            ],
            'day_high' => [
                'type' => 'DECIMAL',
                'constraint' => '15,2',
                'after' => 'previous_close'
            ],
            'day_low' => [
                'type' => 'DECIMAL',
                'constraint' => '15,2',
                'after' => 'day_high'
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);

        // Menambahkan Foreign Key ke tabel emiten
        $this->forge->addForeignKey('emiten_id', 'emiten', 'id', 'CASCADE', 'CASCADE');

        $this->forge->createTable('stock_data');
    }

    public function down()
    {
        $this->forge->dropTable('stock_data');
    }
}