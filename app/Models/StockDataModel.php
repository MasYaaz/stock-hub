<?php

namespace App\Models;

use CodeIgniter\Model;

class StockDataModel extends Model
{
    protected $table = 'stock_data';
    protected $primaryKey = 'id';

    // Daftarkan semua kolom baru agar bisa di-insert/update
    // Pastikan per, roe, der, dan dividend sudah masuk di sini
    protected $allowedFields = [
        'emiten_id',
        'last_price',
        'previous_close',
        'day_high',
        'day_low',
        'market_cap',
        'pbv',
        'per',            // Tambahan
        'roe',            // Tambahan (untuk current data)
        'der',            // Tambahan (untuk current data)
        'dividend',       // Tambahan (Nominal)
        'dividend_yield', // Persentase
        'beta',
        'employees',
        'price_updated_at',
        'fundamental_updated_at'
    ];

    // Aktifkan useTimestamps jika kamu punya kolom created_at/updated_at di tabel
    protected $useTimestamps = false;
    protected $dateFormat = 'datetime';

    /**
     * Mengambil ringkasan stok untuk halaman dashboard utama.
     */
    public function getStockSummary()
    {
        return $this->select('emiten.code, emiten.name, emiten.sector, emiten.notation, emiten.image, stock_data.*')
            ->join('emiten', 'emiten.id = stock_data.emiten_id')
            ->orderBy('stock_data.price_updated_at', 'DESC')
            ->findAll();
    }

    /**
     * Mempermudah pencarian data stok berdasarkan ID emiten
     */
    public function getByEmitenId($emitenId)
    {
        return $this->where('emiten_id', $emitenId)->first();
    }
}