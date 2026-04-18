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
    public function fetchStepByStep(int $limit = 50)
    {
        // Mencegah script timeout karena adanya jeda usleep
        set_time_limit(0);

        // 1. Ambil antrian data basi dengan JOIN agar lebih efisien (hemat query)
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
            $symbol = ($item['code'] === 'IHSG') ? '^JKSE' : strtoupper($item['code']) . ".JK";
            $end = time();
            $start = strtotime('-7 days', $end); // Ambil 7 hari agar aman melompati long weekend

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

                    // Filter data null (Yahoo sering kirim null di hari libur)
                    $cleanPrices = array_values(array_filter($closePrices, fn($p) => $p !== null));
                    $count = count($cleanPrices);

                    if ($count > 0) {
                        $lastPrice = (float) $cleanPrices[$count - 1];
                        $lastDataDate = date('Y-m-d', end($timestamps));

                        /**
                         * LOGIKA HARI LIBUR:
                         * Jika tanggal data terakhir bukan hari ini, berarti market libur.
                         * Set previous_close = lastPrice agar change = 0%.
                         */
                        if ($lastDataDate !== $today) {
                            $truePrevClose = $lastPrice;
                        } else {
                            // Jika market buka, previous close adalah harga sebelum harga terakhir
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
                    // Update timestamp saja agar tidak nyangkut di antrian awal
                    $this->stockDataModel->update($item['id'], ['price_updated_at' => date('Y-m-d H:i:s')]);
                    $failCount++;
                }

                // Jeda agar tidak dianggap spammer oleh Yahoo
                usleep(rand(1000000, 2000000));

            } catch (\Exception $e) {
                $this->stockDataModel->update($item['id'], ['price_updated_at' => date('Y-m-d H:i:s')]);
                $failCount++;
            }
        }

        return "Sync: {$successCount} OK, {$failCount} Fail.";
    }
}