<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateStockHistories extends Migration
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
            'period' => [
                'type' => 'ENUM',
                'constraint' => ['Q1', 'Q2', 'Q3', 'Q4', 'FY'],
                'default' => 'FY', // Full Year
            ],
            'year' => [
                'type' => 'YEAR',
            ],
            'revenue' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ],
            'net_profit' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ],
            'eps' => [
                'type' => 'DECIMAL',
                'constraint' => '15,2',
                'default' => 0.00,
            ],
            'roe' => [
                'type' => 'DECIMAL',
                'constraint' => '5,2',
                'default' => 0.00,
            ],
            'der' => [
                'type' => 'DECIMAL',
                'constraint' => '5,2',
                'default' => 0.00,
            ],
            'pbv' => [
                'type' => 'DECIMAL',
                'constraint' => '5,2',
                'default' => 0.00,
            ],
            'per' => [
                'type' => 'DECIMAL',
                'constraint' => '5,2',
                'default' => 0.00,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('emiten_id', 'emiten', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('stock_histories');
    }

    public function down()
    {
        $this->forge->dropTable('stock_histories');
    }
}