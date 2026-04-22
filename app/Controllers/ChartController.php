<?php

namespace App\Controllers;

use App\Models\EmitenModel;
use App\Models\StockDataModel;
use Config\Services;

class ChartController extends BaseController
{
    /**
     * Endpoint utama untuk mengambil histori chart
     */
    public function get_history($symbol, $range = '1d')
    {
        date_default_timezone_set('Asia/Jakarta');

        $cacheKey = "stock_history_{$symbol}_{$range}";
        if ($cachedData = cache($cacheKey)) {
            return $this->response->setJSON($cachedData);
        }

        // 1. Tentukan Parameter Fetching
        [$start, $interval] = $this->parseRangeParameters($range);
        $ticker = ($symbol === 'IHSG') ? '^JKSE' : strtoupper($symbol) . ".JK";

        // 2. Fetch Data dari Yahoo Finance
        try {
            $rawResult = $this->fetchFromYahoo($ticker, $start, $interval);
            if (!$rawResult) {
                return $this->response->setJSON(['labels' => [], 'prices' => [], 'msg' => 'Data tidak tersedia']);
            }

            // 3. Transform & Clean Data
            $allData = $this->transformRawData($rawResult);
            if (empty($allData)) {
                return $this->response->setJSON(['labels' => [], 'prices' => []]);
            }

            // 4. Filter berdasarkan Range (1d vs Others)
            $latestEntry = end($allData);
            $latestDateInData = $latestEntry['date_key'];
            [$labels, $prices] = $this->filterByRange($allData, $range, $latestDateInData);

            // 5. Kalkulasi Reference & Change
            $referencePrice = $this->calculateReference($symbol, $range, $prices, $latestEntry['price']);
            $changeRaw = $latestEntry['price'] - $referencePrice;
            $changePct = ($referencePrice > 0) ? ($changeRaw / $referencePrice) * 100 : 0;

            $finalResponse = [
                'symbol' => $symbol,
                'last_price' => $latestEntry['price'],
                'reference_price' => $referencePrice,
                'change_raw' => round($changeRaw, 2),
                'change_pct' => round($changePct, 2),
                'last_date' => $latestDateInData,
                'is_market_open' => ($latestDateInData === date('Y-m-d')),
                'labels' => $labels,
                'prices' => $prices,
            ];

            cache()->save($cacheKey, $finalResponse, 120);
            return $this->response->setJSON($finalResponse);

        } catch (\Exception $e) {
            return $this->response->setStatusCode(500)->setJSON(['error' => $e->getMessage()]);
        }
    }

    /**
     * Mengubah range input menjadi timestamp start dan interval string
     */
    private function parseRangeParameters(string $range): array
    {
        $end = time();
        return match ($range) {
            '1d' => [strtotime('-5 days', $end), '1m'],
            '1w' => [strtotime('-20 days', $end), '30m'],
            '1m' => [strtotime('-60 days', $end), '1d'],
            '6m' => [strtotime('-210 days', $end), '1d'],
            default => [strtotime('-400 days', $end), '1d'],
        };
    }

    /**
     * Request ke API Yahoo Finance
     */
    private function fetchFromYahoo($ticker, $start, $interval)
    {
        $end = time();
        $url = "https://query1.finance.yahoo.com/v8/finance/chart/{$ticker}?period1={$start}&period2={$end}&interval={$interval}";

        $client = Services::curlrequest();
        $response = $client->request('GET', $url, [
            'headers' => ['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'],
            'verify' => false,
            'timeout' => 15
        ]);

        $body = json_decode($response->getBody(), true);
        return $body['chart']['result'][0] ?? null;
    }

    /**
     * Membersihkan data null dan merapikan struktur data
     */
    private function transformRawData(array $result): array
    {
        $timestamps = $result['timestamp'] ?? [];
        $closePrices = $result['indicators']['quote'][0]['close'] ?? [];
        $data = [];

        foreach ($timestamps as $key => $ts) {
            if (isset($closePrices[$key]) && $closePrices[$key] !== null) {
                $data[] = [
                    'ts' => (int) $ts,
                    'price' => (float) $closePrices[$key],
                    'date_key' => date('Y-m-d', $ts)
                ];
            }
        }
        return $data;
    }

    /**
     * Memisahkan labels dan prices berdasarkan tipe chart
     */
    private function filterByRange(array $allData, string $range, string $latestDate): array
    {
        $labels = [];
        $prices = [];

        foreach ($allData as $item) {
            if ($range === '1d' && $item['date_key'] !== $latestDate)
                continue;

            $labels[] = match ($range) {
                '1d' => date('H:i', $item['ts']),
                '1w' => date('d M H:i', $item['ts']),
                default => date('d M y', $item['ts']),
            };
            $prices[] = $item['price'];
        }

        return [$labels, $prices];
    }

    /**
     * Logika penentuan harga referensi (mencegah change di akhir pekan)
     */
    private function calculateReference($symbol, $range, $cleanPrices, $lastPrice): float
    {
        if ($range !== '1d') {
            return (float) ($cleanPrices[0] ?? $lastPrice);
        }

        // Khusus 1D: Cek Akhir Pekan (Sabtu = 6, Minggu = 7)
        if (date('N') >= 6) {
            return (float) $lastPrice; // Change % jadi 0
        }

        // Hari Kerja: Ambil Prev Close dari Database
        $emitenModel = new EmitenModel();
        $stockModel = new StockDataModel();

        $emiten = $emitenModel->where('code', $symbol)->first();
        if ($emiten) {
            $stock = $stockModel->where('emiten_id', $emiten['id'])->first();
            if (!empty($stock['previous_close'])) {
                return (float) $stock['previous_close'];
            }
        }

        return (float) ($cleanPrices[0] ?? $lastPrice);
    }
}