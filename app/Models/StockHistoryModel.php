<?php

namespace App\Models;

use CodeIgniter\Model;

class StockHistoryModel extends Model
{
    protected $table = 'stock_histories';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $protectFields = true;

    // Sesuaikan dengan kolom di migrasi tadi
    protected $allowedFields = [
        'emiten_id',
        'period',
        'year',
        'revenue',
        'net_profit',
        'eps',
        'roe',
        'der',
        'pbv',
        'per'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    /**
     * Mengambil tren data untuk analisis AI
     * * @param int $emitenId
     * @param int $limit Jumlah tahun ke belakang
     * @return array
     */
    public function getTrendByEmiten($emitenId, $limit = 3)
    {
        return $this->where('emiten_id', $emitenId)
            ->orderBy('year', 'DESC')
            ->orderBy('period', 'DESC')
            ->findAll($limit);
    }

    /**
     * Cek apakah data tahun/periode tertentu sudah ada
     * Biar tidak duplikat saat scraping
     */
    public function isExist($emitenId, $year, $period = 'FY')
    {
        return $this->where([
            'emiten_id' => $emitenId,
            'year' => $year,
            'period' => $period
        ])->first() !== null;
    }
}