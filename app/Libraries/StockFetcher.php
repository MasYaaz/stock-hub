<?php

namespace App\Libraries;

use App\Models\EmitenModel;
use Config\Services;

class StockFetcher
{
    protected $emitenModel;
    protected $client;

    public function __construct()
    {
        $this->emitenModel = new EmitenModel();
        $this->client = Services::curlrequest();
    }

    /**
     * Cek apakah IHSG sedang dalam jam operasional (WIB)
     */
    private function isMarketOpen(): bool
    {
        // Set timezone ke Jakarta agar akurat dengan jam bursa
        date_default_timezone_set('Asia/Jakarta');

        $day = date('N'); // 1 (Senin) - 7 (Minggu)
        $time = date('H:i');

        // Market tutup di hari Sabtu (6) dan Minggu (7)
        if ($day > 5)
            return false;

        /**
         * Jam Bursa Indonesia (Normal): 
         * Sesi 1 & 2 sekitar jam 09:00 - 16:00
         * Kita set range 08:59 - 16:10 untuk menangkap data pre-opening & post-closing
         */
        if ($time < '08:59' || $time > '16:10') {
            return false;
        }

        return true;
    }

    public function fetchStepByStep(int $limit = 50)
    {
        set_time_limit(0);
        date_default_timezone_set('Asia/Jakarta');

        $isOpen = $this->isMarketOpen();
        $today = date('Y-m-d');

        // 1. Ambil antrian data
        $queue = $this->emitenModel
            ->orderBy('price_updated_at', 'ASC')
            ->limit($limit)
            ->findAll();

        if (empty($queue))
            return "Antrian kosong.";

        $successCount = 0;
        $failCount = 0;

        $headers = [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
            'Referer' => 'https://finance.yahoo.com/',
        ];

        foreach ($queue as $item) {
            /**
             * LOGIKA PRIORITAS:
             * 1. Jika market sedang BUKA (09:00 - 16:00), maka hajar terus fetching-nya (real-time update).
             * 2. Jika market TUTUP, cek: Apakah data terakhir di-update BUKAN hari ini (atau NULL)?
             * - Jika ya (data basi), maka tetap fetch (untuk melengkapi data hari itu).
             * - Jika tidak (sudah update hari ini & market tutup), maka stop/skip.
             */
            $lastUpdateDate = $item['price_updated_at'] ? date('Y-m-d', strtotime($item['price_updated_at'])) : null;

            if (!$isOpen && $lastUpdateDate === $today) {
                // Market tutup DAN data sudah fresh hari ini, tidak perlu fetch lagi emiten ini.
                continue;
            }

            $symbol = ($item['code'] === 'IHSG') ? '^JKSE' : strtoupper($item['code']) . ".JK";
            $url = "https://query1.finance.yahoo.com/v8/finance/chart/{$symbol}?range=1d&interval=1m";

            try {
                $response = $this->client->request('GET', $url, [
                    'headers' => $headers,
                    'verify' => false,
                    'timeout' => 5
                ]);

                $body = json_decode($response->getBody(), true);
                $result = $body['chart']['result'][0] ?? null;

                if ($result) {
                    $meta = $result['meta'];
                    $lastPrice = (float) ($meta['regularMarketPrice'] ?? 0);

                    if ($lastPrice > 0) {
                        $this->emitenModel->update($item['id'], [
                            'last_price' => $lastPrice,
                            'previous_close' => (float) ($meta['chartPreviousClose'] ?? $lastPrice),
                            'day_high' => (float) ($meta['regularMarketDayHigh'] ?? $lastPrice),
                            'day_low' => (float) ($meta['regularMarketDayLow'] ?? $lastPrice),
                            'price_updated_at' => date('Y-m-d H:i:s')
                        ]);
                        $successCount++;
                    }
                }

                // Jeda milidetik (Ryzen 5500 Friendly)
                usleep(rand(300000, 600000));

            } catch (\Exception $e) {
                // Update timestamp agar tidak nyangkut di antrian pertama terus jika error
                $this->emitenModel->update($item['id'], ['price_updated_at' => date('Y-m-d H:i:s')]);
                $failCount++;
                if (str_contains($e->getMessage(), '429'))
                    sleep(5);
            }
        }

        return "Sync Selesai: {$successCount} OK, {$failCount} Fail. Mode: " . ($isOpen ? 'Real-time' : 'Catch-up (Market Closed)');
    }
}