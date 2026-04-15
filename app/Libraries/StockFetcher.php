<?php

namespace App\Libraries;

use App\Models\EmitenModel;
use App\Models\StockDataModel;
use Config\Services;

class StockFetcher
{
    protected $emitenModel;
    protected $stockDataModel;
    protected $client;

    public function __construct()
    {
        $this->emitenModel = new EmitenModel();
        $this->stockDataModel = new StockDataModel();
        $this->client = Services::curlrequest();
    }

    /**
     * Mengambil data saham secara bertahap dari Yahoo Finance
     */
    public function fetchStepByStep(int $limit = 200)
    {
        // 1. Ambil antrian berdasarkan updated_at paling lama (prioritas data basi)
        $queue = $this->stockDataModel->orderBy('updated_at', 'ASC')->limit($limit)->findAll();

        if (empty($queue)) {
            return "Antrian kosong. Pastikan data emiten sudah diinisialisasi.";
        }

        $successCount = 0;
        $failCount = 0;

        foreach ($queue as $item) {
            // Ambil kode emiten (Contoh: ADRO)
            $emiten = $this->emitenModel->find($item['emiten_id']);

            if (!$emiten) {
                $failCount++;
                continue;
            }

            // Tambahkan suffix .JK untuk Bursa Efek Indonesia
            $symbol = $emiten['code'] . ".JK";
            $url = "https://query1.finance.yahoo.com/v8/finance/chart/{$symbol}?interval=1d&range=1d";

            try {
                $response = $this->client->request('GET', $url, [
                    'headers' => [
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                        'Accept' => 'application/json',
                    ],
                    'verify' => false, // Penting untuk localhost agar tidak error SSL
                    'timeout' => 10
                ]);

                $body = json_decode($response->getBody(), true);
                $result = $body['chart']['result'][0] ?? null;

                // 2. Validasi apakah data meta tersedia
                if ($result && isset($result['meta']['regularMarketPrice'])) {
                    $meta = $result['meta'];

                    // 3. Simpan data ke MariaDB sesuai struktur terbaru
                    $this->stockDataModel->update($item['id'], [
                        'last_price' => $meta['regularMarketPrice'] ?? 0,
                        'previous_close' => $meta['chartPreviousClose'] ?? 0,
                        'day_high' => $meta['regularMarketDayHigh'] ?? 0,
                        'day_low' => $meta['regularMarketDayLow'] ?? 0,
                        'updated_at' => date('Y-m-d H:i:s') // Reset antrian ke waktu terbaru
                    ]);

                    $successCount++;
                } else {
                    // Jika data meta tidak ada, tetap update timestamp agar tidak stuck di antrian
                    $this->stockDataModel->update($item['id'], ['updated_at' => date('Y-m-d H:i:s')]);
                    $failCount++;
                    log_message('debug', "Data tidak ditemukan untuk simbol: {$symbol}");
                }

                // 4. Jeda 1 detik untuk menghindari Rate Limit (IP Banned)
                sleep(1);

            } catch (\Exception $e) {
                // Catat error ke log CI4 jika terjadi kegagalan koneksi
                log_message('error', "Fetch Error [{$symbol}]: " . $e->getMessage());

                // Tetap update timestamp agar antrian terus berputar
                $this->stockDataModel->update($item['id'], ['updated_at' => date('Y-m-d H:i:s')]);
                $failCount++;
            }
        }

        return "Proses Selesai: {$successCount} Berhasil, {$failCount} Gagal.";
    }
}