<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEmitenTable extends Migration
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
            // --- INFORMASI PROFIL ---
            'code' => [
                'type' => 'VARCHAR',
                'constraint' => '10', // Ditingkatkan ke 10 untuk jaga-jaga kode unik bursa
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
            ],
            'sector' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],

            // --- DATA HARGA (Real-time/Volatile) ---
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

            // --- DATA FUNDAMENTAL (Semi-Statis) ---
            'market_cap' => [
                'type' => 'DECIMAL',
                'constraint' => '25,2', // Ditingkatkan untuk menampung Market Cap ribuan triliun (T)
                'null' => true,
            ],
            'pbv' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => true,
            ],
            'per' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => true,
            ],
            'roe' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => true,
            ],
            'der' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => true,
            ],
            'dividend' => [
                'type' => 'DECIMAL',
                'constraint' => '15,2',
                'null' => true,
            ],
            'dividend_yield' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => true,
            ],
            'beta' => [
                'type' => 'DECIMAL',
                'constraint' => '8,3',
                'null' => true,
            ],

            // --- TRACKING & TIMESTAMPS ---
            'price_updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'fundamental_updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
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
        $this->forge->addUniqueKey('code');

        // Indexing untuk mempercepat filter pencarian
        $this->forge->addKey('sector');

        $this->forge->createTable('emiten');
    }

    public function down()
    {
        $this->forge->dropTable('emiten');
    }
}