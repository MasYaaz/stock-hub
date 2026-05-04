<?php
namespace App\Controllers;

use App\Models\EmitenModel;

class DashboardController extends BaseController
{
    public function index(): string
    {
        $emitenModel = new EmitenModel();

        if ($emitenModel->countAllResults() == 0) {
            $this->initializeAllStocks();
        }

        // Ambil rekomendasi cerdas (Logika yang menggabungkan history 5 tahun)
        $recommendations = $emitenModel->getSmartRecommendations();

        $data = [
            'title' => 'Dashboard BedahSaham',
            'stocks' => $emitenModel->getStockSummary(),
            'total' => $emitenModel->countAllResults(),
            'best_picks' => $recommendations
        ];
        return view('stock_index', $data);
    }

    /**
     * Endpoint API untuk Real-time Update Dashboard
     */
    public function get_live_data()
    {
        $emitenModel = new EmitenModel();
        return $this->response->setJSON($emitenModel->getStockSummary());
    }

    /**
     * Inisialisasi daftar emiten Indonesia dari JSON
     * Disesuaikan dengan struktur Dual Timestamps (Price & Fundamental)
     */
    private function initializeAllStocks()
    {
        // Tingkatkan limit waktu karena operasi I/O dan DB untuk 200 baris lumayan berat
        set_time_limit(300);

        $emitenModel = new EmitenModel();

        $jsonPath = WRITEPATH . 'data/all_stocks.json';

        if (!file_exists($jsonPath)) {
            log_message('error', "File all_stocks.json tidak ditemukan di " . $jsonPath);
            return;
        }

        $allStocks = json_decode(file_get_contents($jsonPath), true);

        if (is_array($allStocks)) {
            // Gunakan Transaction agar data konsisten (Atomicity)
            $db = \Config\Database::connect();
            $db->transStart();

            foreach ($allStocks as $s) {
                // 1. Cek duplikasi agar tidak double insert
                $existing = $emitenModel->where('code', $s['code'])->first();
                if ($existing)
                    continue;

                // 2. Masukkan ke tabel emiten
                // Deskripsi dan Image dibiarkan NULL dulu, akan diisi saat detail dibuka
                $emitenModel->insert([
                    'code' => $s['code'],
                    'name' => $s['name'],
                    'sector' => $s['sector'] ?? 'Unknown',
                    'notation' => $s['notation'],
                    'last_price' => 0,
                    'price_updated_at' => '2000-01-01 00:00:00',
                    'fundamental_updated_at' => '2000-01-01 00:00:00'
                ]);
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                log_message('error', "Gagal menginisialisasi daftar saham.");
            }
        }
    }
}