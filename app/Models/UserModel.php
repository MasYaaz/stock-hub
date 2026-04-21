<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'object';
    protected $allowedFields = ['username', 'email', 'password', 'token_balance'];

    protected $useTimestamps = true;

    /**
     * Memotong saldo token user
     */
    public function deductToken($userId, $amount = 1)
    {
        // Ambil data user terbaru untuk cek saldo
        $user = $this->find($userId);

        // Validasi: Jika user tidak ada atau saldo kurang
        if (!$user || $user->token_balance < $amount) {
            // Melempar exception agar transaksi di Controller otomatis gagal/rollback
            throw new \Exception("Saldo token tidak mencukupi atau user tidak ditemukan.");
        }

        // Jalankan Update
        return $this->db->table($this->table)
            ->where('id', $userId)
            ->set('token_balance', 'token_balance - ' . (int) $amount, false)
            ->update();
    }

    /**
     * Menambah saldo token (saat top up)
     */
    public function addToken($userId, $amount)
    {
        return $this->db->table($this->table)
            ->where('id', $userId)
            ->set('token_balance', 'token_balance + ' . (int) $amount, false)
            ->update();
    }
}