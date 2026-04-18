<?php

namespace App\Models;

use CodeIgniter\Model;

class EmitenModel extends Model
{
    protected $table = 'emiten';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $protectFields = true;

    // Kolom yang boleh diisi (mass assignment)
    protected $allowedFields = [
        'code',
        'name',
        'sector',
        'notation',
        'description',
        'image',
        'ai_analysis',
        'last_ai_update'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    /**
     * Opsional: Method untuk mempermudah pencarian berdasarkan kode saham
     */
    public function getByCode($code)
    {
        return $this->where('code', $code)->first();
    }
}