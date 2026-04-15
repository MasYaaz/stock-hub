<?php

namespace App\Models;

use CodeIgniter\Model;

class StockDataModel extends Model
{
    protected $table = 'stock_data';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'emiten_id',
        'last_price',
        'previous_close',
        'day_high',
        'day_low',
        'updated_at'
    ];

    public function getStockSummary()
    {
        return $this->select('emiten.code, emiten.name, emiten.sector, stock_data.*')
            ->join('emiten', 'emiten.id = stock_data.emiten_id')
            ->orderBy('stock_data.updated_at', 'DESC')
            ->findAll();
    }
}