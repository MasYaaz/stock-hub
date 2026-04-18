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
            'code' => [
                'type' => 'VARCHAR',
                'constraint' => '5',
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
            ],
            'sector' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ],
            'notation' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Profil bisnis perusahaan dari FMP'
            ],
            'image' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'comment' => 'URL logo perusahaan'
            ],
            'ai_analysis' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'last_ai_update' => [
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
        $this->forge->addUniqueKey('code'); // Proteksi agar kode saham tidak double
        $this->forge->createTable('emiten');
    }

    public function down()
    {
        $this->forge->dropTable('emiten');
    }
}