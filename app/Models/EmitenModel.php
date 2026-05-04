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

    public function getSmartRecommendations()
    {
        // 1. Longgarkan filter awal agar saham Growth (PER tinggi) tetap masuk
        $stocks = $this->where('last_price >', 0)
            ->where('roe >', 5) // Minimal perusahaan sehat
            ->findAll();

        $historyModel = new \App\Models\StockHistoryModel();
        $finalRecommendations = [];

        foreach ($stocks as $stock) {
            $histories = $historyModel->where('emiten_id', $stock['id'])
                ->orderBy('year', 'DESC')
                ->findAll(5);

            if (count($histories) < 3)
                continue;

            // Bersihkan data angka dari kemungkinan null atau string kosong
            $currentNetProfit = (float) ($histories[0]['net_profit'] ?? 0);
            $oldNetProfit = (float) ($histories[count($histories) - 1]['net_profit'] ?? 0);
            $currentRevenue = (float) ($histories[0]['revenue'] ?? 0);
            $oldRevenue = (float) ($histories[count($histories) - 1]['revenue'] ?? 0);

            // --- Metrik ---
            $avgRoe = array_sum(array_column($histories, 'roe')) / count($histories);

            // Revenue Growth: Cek apakah pendapatan sekarang lebih besar dari awal periode (5 thn lalu)
            $revenueGrowth = ($currentRevenue > $oldRevenue);

            // Profit Consistency: Selalu untung dalam 5 tahun terakhir
            $isNetProfitPositive = count(array_filter($histories, fn($h) => (float) $h['net_profit'] > 0)) == count($histories);

            // Tren Laba: Naik dalam 3 tahun terakhir
            $isGrowing = $currentNetProfit > (float) ($histories[2]['net_profit'] ?? 0);

            $type = null;
            $score = 0;
            $icon = '';

            // --- Logika Klasifikasi (Urutan Prioritas) ---

            // 1. DIVIDEND ARISTOCRAT (Turunkan yield ke 3% agar lebih realistis di IDX)
            if ($stock['dividend_yield'] >= 3 && $isNetProfitPositive && $stock['der'] < 1.2) {
                $type = 'Dividend Aristocrat';
                $score = 90 + ($stock['dividend_yield'] / 2);
                $icon = 'coins';
            }
            // 2. BLUE CHIP / COMPOUNDER (Market Cap > 40T)
            elseif ($stock['market_cap'] >= 40000000000000 && $isGrowing && $avgRoe > 12) {
                $type = 'Blue Chip / Compounder';
                $score = 92;
                $icon = 'shield-check';
            }
            // 3. GROWTH STOCK (Abaikan batas PER 20, fokus ke Revenue & ROE)
            elseif ($revenueGrowth && $avgRoe > 15 && $currentNetProfit > $oldNetProfit) {
                $type = 'Growth Stock';
                $score = 88;
                $icon = 'trending-up';
            }
            // 4. VALUE INVESTING (Undervalued)
            elseif ($stock['pbv'] < 1.1 && $stock['per'] < 12 && $isNetProfitPositive) {
                $type = 'Value Investing (Undervalued)';
                $score = 85;
                $icon = 'gem';
            }
            // 5. HIGH EFFICIENCY
            elseif ($stock['roe'] > 18 && $stock['der'] < 1) {
                $type = 'High Efficiency';
                $score = 84;
                $icon = 'zap';
            }

            if ($type !== null) {
                $stock['recommendation_type'] = $type;
                $stock['score'] = (int) min($score, 99); // Cap di 99
                $stock['icon'] = $icon;
                $finalRecommendations[] = $stock;
            }
        }

        // Urutkan berdasarkan skor
        usort($finalRecommendations, fn($a, $b) => $b['score'] <=> $a['score']);

        return $finalRecommendations;
    }
}