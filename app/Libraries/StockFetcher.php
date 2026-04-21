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
        // LOGIKA BARU: Jika market tutup, langsung berhenti.
        if (!$this->isMarketOpen()) {
            return "Market sedang tutup (Libur/Luar Jam Kerja). Fetching dibatalkan.";
        }

        set_time_limit(0);

        // 1. Ambil antrian data
        $queue = $this->stockDataModel
            ->select('stock_data.*, emiten.code')
            ->join('emiten', 'emiten.id = stock_data.emiten_id')
            ->orderBy('price_updated_at', 'ASC')
            ->limit($limit)
            ->findAll();

        if (empty($queue))
            return "Antrian kosong.";

        $successCount = 0;
        $failCount = 0;
        $today = date('Y-m-d');
        $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36';

        foreach ($queue as $item) {
            // DOUBLE CHECK: Cek lagi di tengah loop (opsional)
            // Berguna jika limit sangat besar sehingga loop memakan waktu berjam-jam
            if (!$this->isMarketOpen())
                break;

            $symbol = ($item['code'] === 'IHSG') ? '^JKSE' : strtoupper($item['code']) . ".JK";
            $end = time();
            $start = strtotime('-7 days', $end);

            $url = "https://query1.finance.yahoo.com/v8/finance/chart/{$symbol}?period1={$start}&period2={$end}&interval=1d";

            try {
                $response = $this->client->request('GET', $url, [
                    'headers' => [
                        'User-Agent' => $userAgent,
                        'Accept' => 'application/json',
                    ],
                    'verify' => false,
                    'timeout' => 10
                ]);

                $body = json_decode($response->getBody(), true);
                $result = $body['chart']['result'][0] ?? null;

                if ($result && isset($result['timestamp'])) {
                    $meta = $result['meta'];
                    $timestamps = $result['timestamp'];
                    $closePrices = $result['indicators']['quote'][0]['close'] ?? [];

                    $cleanPrices = array_values(array_filter($closePrices, fn($p) => $p !== null));
                    $count = count($cleanPrices);

                    if ($count > 0) {
                        $lastPrice = (float) $cleanPrices[$count - 1];
                        $lastDataDate = date('Y-m-d', end($timestamps));

                        // Logika Prev Close saat Market Buka vs Tutup
                        if ($lastDataDate !== $today) {
                            $truePrevClose = $lastPrice;
                        } else {
                            $truePrevClose = $count > 1 ? (float) $cleanPrices[$count - 2] : (float) ($meta['chartPreviousClose'] ?? $lastPrice);
                        }

                        $this->stockDataModel->update($item['id'], [
                            'last_price' => $lastPrice,
                            'previous_close' => $truePrevClose,
                            'day_high' => $meta['regularMarketDayHigh'] ?? $lastPrice,
                            'day_low' => $meta['regularMarketDayLow'] ?? $lastPrice,
                            'price_updated_at' => date('Y-m-d H:i:s')
                        ]);
                        $successCount++;
                    } else {
                        throw new \Exception("Data harga kosong");
                    }
                } else {
                    $this->stockDataModel->update($item['id'], ['price_updated_at' => date('Y-m-d H:i:s')]);
                    $failCount++;
                }

                usleep(rand(1000000, 2000000));

            } catch (\Exception $e) {
                $this->stockDataModel->update($item['id'], ['price_updated_at' => date('Y-m-d H:i:s')]);
                $failCount++;
            }
        }

        return "Sync: {$successCount} OK, {$failCount} Fail. Market Open: " . ($this->isMarketOpen() ? 'YES' : 'NO');
    }
}