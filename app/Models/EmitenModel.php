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

    // Gabungan semua kolom dari Emiten dan StockData
    protected $allowedFields = [
        'code',
        'name',
        'sector',
        'description',

        // Data Harga (Real-time/Volatile)
        'last_price',
        'previous_close',
        'day_high',
        'day_low',

        // Data Fundamental (Semi-Statis)
        'market_cap',
        'pbv',
        'per',
        'roe',
        'der',
        'dividend',
        'dividend_yield',
        'beta',

        // Tracking Update
        'price_updated_at',
        'fundamental_updated_at'
    ];

    // Dates (Aktifkan timestamps untuk tracking record baru)
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    /**
     * Mengambil ringkasan stok untuk dashboard.
     * Sekarang jauh lebih simpel, tidak perlu JOIN lagi.
     */
    public function getStockSummary()
    {
        return $this->orderBy('price_updated_at', 'DESC')
            ->findAll();
    }

    /**
     * Mencari emiten berdasarkan kode saham (Ticker)
     */
    public function getByCode($code)
    {
        return $this->where('code', strtoupper($code))->first();
    }

    /**
     * Mempermudah filter berdasarkan sektor
     */
    public function getBySector($sector)
    {
        return $this->where('sector', $sector)->findAll();
    }
}