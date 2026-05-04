<?php

namespace App\Libraries;

use App\Models\EmitenModel;
use App\Models\StockHistoryModel;
use Config\Services;

class StockFetcher
{
    protected $emitenModel;
    protected $historyModel;
    protected $client;

    public function __construct()
    {
        $this->emitenModel = new EmitenModel();
        $this->historyModel = new StockHistoryModel();
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

    /**
     * Fungsi Utama: Mengambil harga real-time dan fundamental secara bergantian
     */
    public function fetchStepByStep(int $limit = 50)
    {
        set_time_limit(0);
        date_default_timezone_set('Asia/Jakarta');

        $isOpen = $this->isMarketOpen();
        $today = date('Y-m-d');
        $now = time();

        // 1. Ambil antrian data (berdasarkan yang paling lama tidak diupdate harganya)
        $queue = $this->emitenModel
            ->orderBy('price_updated_at', 'ASC')
            ->limit($limit)
            ->findAll();

        if (empty($queue))
            return "Antrian kosong.";

        $successCount = 0;
        $failCount = 0;
        $fundamentalCount = 0;

        foreach ($queue as $item) {
            $code = strtoupper($item['code']);

            // --- LOGIKA 1: UPDATE FUNDAMENTAL (Sekali Sehari) ---
            // Jika fundamental belum diupdate hari ini, jalankan updateFundamental
            $lastFundUpdate = $item['fundamental_updated_at'] ? strtotime($item['fundamental_updated_at']) : 0;

            if (($now - $lastFundUpdate) > 86400) {
                if ($this->updateFundamental($code)) {
                    $fundamentalCount++;
                    // Refresh data item setelah update fundamental agar harga terbaru ikut terbawa
                    $item = $this->emitenModel->find($item['id']);
                }
            }

            // --- LOGIKA 2: UPDATE HARGA REAL-TIME (Yahoo Finance) ---
            $lastPriceUpdateDate = $item['price_updated_at'] ? date('Y-m-d', strtotime($item['price_updated_at'])) : null;

            // Lewati jika market tutup dan data sudah diupdate hari ini
            if (!$isOpen && $lastPriceUpdateDate === $today) {
                continue;
            }

            // Eksekusi Fetch Harga
            if ($this->fetchPriceYahoo($item)) {
                $successCount++;
            } else {
                $failCount++;
            }

            // Jeda milidetik (Ryzen 5500 Friendly)
            usleep(rand(50000, 100000));
        }

        return "Sync Selesai. Harga: {$successCount} OK, {$failCount} Fail. Fundamental: {$fundamentalCount} Updated. Mode: " . ($isOpen ? 'Real-time' : 'Catch-up');
    }

    /**
     * Logika pengambilan harga dari Yahoo Finance
     */
    private function fetchPriceYahoo($item)
    {
        $symbol = ($item['code'] === 'IHSG') ? '^JKSE' : strtoupper($item['code']) . ".JK";
        $url = "https://query1.finance.yahoo.com/v8/finance/chart/{$symbol}?range=1d&interval=1m";

        try {
            $response = $this->client->request('GET', $url, [
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36...',
                    'Referer' => 'https://finance.yahoo.com/',
                ],
                'timeout' => 5
            ]);

            $body = json_decode($response->getBody(), true);
            $result = $body['chart']['result'][0] ?? null;

            if ($result) {
                $meta = $result['meta'];
                $lastPrice = (float) ($meta['regularMarketPrice'] ?? 0);

                if ($lastPrice > 0) {
                    return $this->emitenModel->update($item['id'], [
                        'last_price' => $lastPrice,
                        'previous_close' => (float) ($meta['chartPreviousClose'] ?? $lastPrice),
                        'day_high' => (float) ($meta['regularMarketDayHigh'] ?? $lastPrice),
                        'day_low' => (float) ($meta['regularMarketDayLow'] ?? $lastPrice),
                        'price_updated_at' => date('Y-m-d H:i:s')
                    ]);
                }
            }
        } catch (\Exception $e) {
            // Update timestamp agar antrian jalan terus meski error
            $this->emitenModel->update($item['id'], ['price_updated_at' => date('Y-m-d H:i:s')]);
            if (str_contains($e->getMessage(), '429'))
                sleep(5);
        }
        return false;
    }

    /**
     * Fungsi Utama untuk update Fundamental & History (Pindahan dari Controller)
     */
    public function updateFundamental($code)
    {
        $stock = $this->emitenModel->where('code', $code)->first();
        if (!$stock)
            return false;

        try {
            // 1. Scrape Data
            $profileData = $this->scrapeProfileGoogle($code);
            $scrapedHistory = $this->scrapeFundamentalIPOT($code);
            $beta = $this->scrapeBetaFromMarketWatch($code);

            if ($scrapedHistory && $profileData) {
                // Hitung Yield
                $divYield = ($profileData['last_dividend'] > 0 && $profileData['price'] > 0)
                    ? ($profileData['last_dividend'] / $profileData['price']) * 100 : 0;

                // 2. Update Tabel Emiten
                $this->emitenModel->update($stock['id'], [
                    'description' => $profileData['description'] ?? $stock['description'],
                    'last_price' => $profileData['price'],
                    'market_cap' => $profileData['market_cap'],
                    'dividend' => $profileData['last_dividend'],
                    'dividend_yield' => $divYield,
                    'beta' => $beta ?? $stock['beta'],
                    'pbv' => $scrapedHistory['current']['pbv'] ?? $stock['pbv'],
                    'per' => $scrapedHistory['current']['per'] ?? $stock['per'],
                    'roe' => $scrapedHistory['current']['roe'] ?? $stock['roe'],
                    'der' => $scrapedHistory['current']['der'] ?? $stock['der'],
                    'fundamental_updated_at' => date('Y-m-d H:i:s')
                ]);

                // 3. Update Tabel History Multi-Tahun
                if (isset($scrapedHistory['history']) && is_array($scrapedHistory['history'])) {
                    foreach ($scrapedHistory['history'] as $year => $values) {
                        $historyData = [
                            'emiten_id' => $stock['id'],
                            'year' => $year,
                            'period' => 'FY',
                            'revenue' => (string) ($values['revenue'] ?? '0'),
                            'net_profit' => (string) ($values['net_profit'] ?? '0'),
                            'eps' => $values['eps'] ?? 0,
                            'roe' => $values['roe'] ?? 0,
                            'der' => $values['der'] ?? 0,
                            'pbv' => $values['pbv'] ?? 0,
                            'per' => $values['per'] ?? 0,
                        ];

                        $existing = $this->historyModel->where([
                            'emiten_id' => $stock['id'],
                            'year' => $year,
                            'period' => 'FY'
                        ])->first();

                        $existing
                            ? $this->historyModel->update($existing['id'], $historyData)
                            : $this->historyModel->insert($historyData);
                    }
                }
                return true;
            }
        } catch (\Exception $e) {
            log_message('error', "Fundamental Update Error [{$code}]: " . $e->getMessage());
        }
        return false;
    }

    private function scrapeFundamentalIPOT($code)
    {
        $client = Services::curlrequest();
        $url = "https://www.indopremier.com/module/saham/include/fundamental.php?code=" . strtoupper($code);

        try {
            $response = $client->request('GET', $url, [
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/123.0.0.0',
                ],
                'timeout' => 15,
                'verify' => false
            ]);

            $html = $response->getBody();
            if (empty($html))
                return null;

            $results = ['current' => [], 'history' => []];

            // Definisi pattern: Revenue & Net Profit sekarang menangkap seluruh row <tr>
            $metrics = [
                'pbv' => '/>PBV<\/td>(.*?)<\/tr>/is',
                'per' => '/>PER<\/td>(.*?)<\/tr>/is',
                'roe' => '/>ROE<\/td>(.*?)<\/tr>/is',
                'der' => '/>Debt\/Equity<\/td>(.*?)<\/tr>/is',
                'revenue' => '/>Revenue<\/td>(.*?)<\/tr>/is',
                'net_profit' => '/>Net\.?Profit<\/td>(.*?)<\/tr>/is',
                'eps' => '/>EPS<\/td>(.*?)<\/tr>/is',
            ];

            foreach ($metrics as $key => $pattern) {
                if (preg_match($pattern, $html, $matches)) {
                    $rowHtml = $matches[1];

                    // Tangkap semua angka/string data di dalam <td>
                    preg_match_all('/<td[^>]*>\s*((?:(?!<\/td>).)*)/i', $rowHtml, $values);
                    $dataArr = array_map('trim', $values[1] ?? []);

                    if (count($dataArr) >= 4) {
                        // Logic pemisahan: String untuk lapkeu, Float untuk rasio
                        $isString = in_array($key, ['revenue', 'net_profit']);

                        // 1. Ambil Data Current (Index 0)
                        $results['current'][$key] = $isString
                            ? $dataArr[0]
                            : (float) str_replace(',', '.', $dataArr[0]);

                        // 2. Mapping History (Index 2 ke atas)
                        // Index mapping: [2]=2024, [3]=2023, [4]=2022, [5]=2021, [6]=2020
                        $years = [2024, 2023, 2022, 2021, 2020];
                        foreach ($years as $i => $year) {
                            $dataIdx = $i + 2;
                            if (isset($dataArr[$dataIdx])) {
                                $results['history'][$year][$key] = $isString
                                    ? $dataArr[$dataIdx]
                                    : (float) str_replace(',', '.', $dataArr[$dataIdx]);
                            }
                        }
                    }
                }
            }

            return $results;

        } catch (\Exception $e) {
            log_message('error', "IPOT Scrape Error [{$code}]: " . $e->getMessage());
            return null;
        }
    }

    private function scrapeProfileGoogle($code)
    {
        $client = Services::curlrequest();
        try {
            // Gunakan URL Beta dengan bahasa Indonesia agar label regex cocok
            $url = "https://www.google.com/finance/beta/quote/" . strtoupper($code) . ":IDX?hl=id";

            $res = $client->request('GET', $url, [
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
                    'Accept-Language' => 'id-ID,id;q=0.9,en-US;q=0.8',
                ],
                'timeout' => 15,
                'verify' => false
            ]);

            $html = $res->getBody();

            // 1. Dapatkan teks bersih tanpa tag HTML agar lebih mudah di-regex
            $cleanText = strip_tags($html);
            // Ubah &nbsp; atau spasi aneh menjadi spasi biasa
            $cleanText = html_entity_decode($cleanText);
            $cleanText = preg_replace('/\s+/u', ' ', $cleanText);

            $data = [
                'description' => null,
                'price' => 0,
                'market_cap' => 0,
                'last_dividend' => 0
            ];

            // 1. EKSTRAK DARI ds:3 (Paling Stabil)
            // Berdasarkan log Anda: ds:3 berisi array panjang.
            // Index [0][0][2] = Deskripsi
            // Index [0][0][8] = Last Price (1325)
            // Index [0][0][7] = Market Cap (55019962500000)
            if (preg_match('/AF_initDataCallback\({key: \'ds:3\'.*?data:(.*?), sideChannel/s', $html, $matches)) {
                $jsonData = json_decode($matches[1], true);
                $root = $jsonData[0][0] ?? null;

                if ($root) {
                    $data['description'] = $root[2] ?? null;
                    $data['price'] = (float) ($root[8] ?? 0);
                    $data['market_cap'] = (float) ($root[7] ?? 0);
                }
            }

            // 2. EKSTRAK DIVIDEN (Rupiah Direct dari HTML)
            // Karena nominal "Rp9" seringkali tidak masuk ke array utama ds:3, 
            // kita gunakan regex teks mentah dengan spasi fleksibel.
            if (preg_match('/Dividen per kuartal<\/div><div[^>]*>Rp\s?([\d\.,]+)<\/div>/u', $html, $divMatches)) {
                $cleanDiv = str_replace(['.', ','], ['', '.'], $divMatches[1]);
                $data['last_dividend'] = (float) $cleanDiv;
            }

            // 3. CLEANING DESCRIPTION (Jika ada link Wikipedia atau spasi aneh)
            if ($data['description']) {
                $data['description'] = html_entity_decode($data['description']);
                $data['description'] = strip_tags($data['description']);
                $data['description'] = preg_replace('/\s+/u', ' ', $data['description']);
                $data['description'] = trim($data['description']);
            }

            return $data;

        } catch (\Exception $e) {
            log_message('error', "Google Beta Scrape Error [{$code}]: " . $e->getMessage());
            return null;
        }
    }

    private function scrapeBetaFromMarketWatch($code)
    {
        $client = Services::curlrequest();
        try {
            // MarketWatch menggunakan suffix khusus untuk bursa Indonesia
            $url = "https://www.marketwatch.com/investing/stock/" . strtolower($code) . "?countrycode=id";

            $res = $client->request('GET', $url, [
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
                    'Referer' => 'https://www.google.com/', // Pura-pura dari Google search
                    'Accept-Language' => 'en-US,en;q=0.9',
                ],
                'timeout' => 20,
                'verify' => false
            ]);

            $html = $res->getBody();

            // Cari label "Beta" lalu ambil nilai di tag <span> setelahnya
            // Struktur: <small class="label">Beta</small> <span class="primary ">0.87</span>
            if (preg_match('/<small class="label">Beta<\/small>\s*<span class="primary\s*">(.*?)<\/span>/s', $html, $matches)) {
                $betaValue = trim($matches[1]);
                return is_numeric($betaValue) ? (float) $betaValue : null;
            }

            return null;
        } catch (\Exception $e) {
            log_message('error', "MarketWatch Beta Scrape Error [{$code}]: " . $e->getMessage());
            return null;
        }
    }
}