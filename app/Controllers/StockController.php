<?php

namespace App\Controllers;

use App\Models\EmitenModel;
use App\Models\StockDataModel;
use App\Models\StockHistoryModel;

class StockController extends BaseController
{
    public function index(): string
    {
        $stockDataModel = new StockDataModel();
        $emitenModel = new EmitenModel();

        if ($emitenModel->countAllResults() == 0) {
            $this->initializeAllStocks();
        }

        $data = [
            'title' => 'Dashboard Stock-Hub',
            'stocks' => $stockDataModel->getStockSummary(),
            'total' => $emitenModel->countAllResults()
        ];
        return view('stock_index', $data);
    }

    public function get_history($symbol, $range = '1d')
    {
        // Jika simbol yang dikirim adalah 'IHSG', arahkan ke ticker Yahoo ^JKSE
        $ticker = ($symbol === 'IHSG') ? '^JKSE' : strtoupper($symbol) . ".JK";

        $end = time();

        // Tentukan waktu mulai dan interval berdasarkan range
        switch ($range) {
            case '1d':
                $start = strtotime('-1 day', $end);
                $interval = '5m'; // Data per 5 menit
                break;
            case '1w':
                $start = strtotime('-7 days', $end);
                $interval = '30m'; // Data per 30 menit
                break;
            case '1m':
                $start = strtotime('-1 month', $end);
                $interval = '1d';
                break;
            case '6m':
                $start = strtotime('-6 months', $end);
                $interval = '1d';
                break;
            case '1y':
            default:
                $start = strtotime('-1 year', $end);
                $interval = '1d';
                break;
        }

        $url = "https://query1.finance.yahoo.com/v8/finance/chart/{$ticker}?period1={$start}&period2={$end}&interval={$interval}";

        $client = \Config\Services::curlrequest();
        try {
            $response = $client->request('GET', $url, [
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
                ],
                'verify' => false,
                'timeout' => 10
            ]);

            $body = json_decode($response->getBody(), true);
            $result = $body['chart']['result'][0] ?? null;

            if (!$result) {
                return $this->response->setJSON(['labels' => [], 'prices' => [], 'msg' => 'No result from Yahoo']);
            }

            $timestamps = $result['timestamp'] ?? [];
            $prices = $result['indicators']['quote'][0]['close'] ?? [];

            $cleanLabels = [];
            $cleanPrices = [];

            date_default_timezone_set('Asia/Jakarta'); // Set global timezone di fungsi ini

            foreach ($timestamps as $key => $ts) {
                if (isset($prices[$key]) && $prices[$key] !== null) {
                    // Format tanggal sesuai range
                    if ($range == '1d') {
                        $cleanLabels[] = date('H:i', $ts);
                    } elseif ($range == '1w') {
                        $cleanLabels[] = date('d M H:i', $ts);
                    } else {
                        $cleanLabels[] = date('d M y', $ts);
                    }
                    $cleanPrices[] = round($prices[$key], 2);
                }
            }

            return $this->response->setJSON([
                'symbol' => $symbol,
                'labels' => $cleanLabels,
                'prices' => $cleanPrices,
                'change_pct' => $this->calculateChange($cleanPrices) // Bonus: hitung % perubahan
            ]);

        } catch (\Exception $e) {
            return $this->response->setStatusCode(500)->setJSON(['error' => $e->getMessage()]);
        }
    }

    // Helper untuk hitung perubahan harga di chart
    private function calculateChange($prices)
    {
        if (count($prices) < 2)
            return 0;
        $start = $prices[0];
        $end = end($prices);
        return round((($end - $start) / $start) * 100, 2);
    }

    /**
     * Endpoint API untuk Real-time Update Dashboard
     */
    public function get_live_data()
    {
        $stockDataModel = new StockDataModel();
        return $this->response->setJSON($stockDataModel->getStockSummary());
    }

    public function detail($code)
    {
        $stockDataModel = new StockDataModel();
        $emitenModel = new EmitenModel();
        $historyModel = new StockHistoryModel();

        // 1. Ambil data awal dari DB
        $stock = $stockDataModel->select('stock_data.*, emiten.code, emiten.name, emiten.sector, emiten.description, emiten.image, emiten.ai_analysis, emiten.last_ai_update')
            ->join('emiten', 'emiten.id = stock_data.emiten_id')
            ->where('emiten.code', $code)
            ->first();

        if (!$stock) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $symbol = $code . ".JK";
        $apiKey = env('FMP_API_KEY');
        $baseUrl = env('FMP_BASE_URL');

        $client = \Config\Services::curlrequest();
        $now = time();
        $needsRefresh = false;

        // --- LOGIKA 1: FUNDAMENTAL (FMP + MULTI-YEAR SCRAPING) ---
        $lastFundamentalUpdate = strtotime($stock['fundamental_updated_at'] ?? '2000-01-01');

        // Update data jika sudah lebih dari 24 jam (86400 detik)
        if (($now - $lastFundamentalUpdate) > 86400) {
            try {
                // A. Fetch FMP (Market Cap, Beta, dsb)
                $fmpUrl = "{$baseUrl}/profile?symbol={$symbol}&apikey={$apiKey}";
                $resFmp = $client->request('GET', $fmpUrl, ['verify' => false]);
                $bodyFmp = json_decode($resFmp->getBody(), true);
                $fmpData = $bodyFmp[0] ?? null;

                // B. Fetch IndoPremier Scraping (PBV, PER, ROE, DER, Histories)
                $scrapedData = $this->scrapeFundamentalIPOT($code);

                if ($fmpData && $scrapedData) {
                    // 1. Hitung Dividend Yield
                    $divYield = ($fmpData['lastDividend'] ?? 0) > 0 && ($fmpData['price'] ?? 0) > 0
                        ? ($fmpData['lastDividend'] / $fmpData['price']) * 100
                        : 0;

                    // 2. Update Data Emiten (Statis)
                    $emitenModel->update($stock['emiten_id'], [
                        'description' => $fmpData['description'] ?? $stock['description'],
                        'image' => $fmpData['image'] ?? $stock['image']
                    ]);

                    // 3. Update Data Fundamental Terbaru (Tabel stock_data)
                    $fundamentalData = [
                        'market_cap' => $fmpData['marketCap'] ?? $stock['market_cap'],
                        'beta' => $fmpData['beta'] ?? $stock['beta'],
                        'dividend_yield' => $divYield,
                        'employees' => $fmpData['fullTimeEmployees'] ?? $stock['employees'],
                        'pbv' => $scrapedData['current']['pbv'] ?? null,
                        'per' => $scrapedData['current']['per'] ?? null,
                        'roe' => $scrapedData['current']['roe'] ?? null,
                        'der' => $scrapedData['current']['der'] ?? null,
                        'dividend' => $fmpData['lastDividend'] ?? null,
                        'fundamental_updated_at' => date('Y-m-d H:i:s')
                    ];
                    $stockDataModel->update($stock['id'], $fundamentalData);

                    // 4. Update Data Sejarah (Tabel stock_histories)
                    if (isset($scrapedData['history']) && is_array($scrapedData['history'])) {
                        foreach ($scrapedData['history'] as $year => $values) {

                            // Data yang akan diproses
                            $historyData = [
                                'emiten_id' => $stock['emiten_id'],
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

                            // Cek apakah data untuk emiten dan tahun tersebut sudah ada
                            $existing = $historyModel->where([
                                'emiten_id' => $stock['emiten_id'],
                                'year' => $year,
                                'period' => 'FY'
                            ])->first();

                            if ($existing) {
                                // Jika ada, TIMPA/UPDATE data lama
                                $historyModel->update($existing['id'], $historyData);
                            } else {
                                // Jika belum ada, baru INSERT
                                $historyModel->insert($historyData);
                            }
                        }
                    }

                    $needsRefresh = true;
                }
            } catch (\Exception $e) {
                log_message('error', "Fundamental Error [{$code}]: " . $e->getMessage());
            }
        }

        // --- LOGIKA 2: HARGA & CHART (YAHOO V8) ---
        $chartData = ['labels' => [], 'prices' => []];
        $yahooUrl = "https://query1.finance.yahoo.com/v8/finance/chart/{$symbol}?range=1y&interval=1wk";

        try {
            $resYahoo = $client->request('GET', $yahooUrl, [
                'headers' => ['User-Agent' => 'Mozilla/5.0'],
                'verify' => false
            ]);
            $bodyYahoo = json_decode($resYahoo->getBody(), true);
            $result = $bodyYahoo['chart']['result'][0] ?? null;

            if ($result) {
                $meta = $result['meta'];
                $timestamps = $result['timestamp'] ?? [];
                $prices = $result['indicators']['quote'][0]['close'] ?? [];

                foreach ($timestamps as $key => $ts) {
                    if (isset($prices[$key]) && $prices[$key] !== null) {
                        $chartData['labels'][] = date('M Y', $ts);
                        $chartData['prices'][] = round($prices[$key], 2);
                    }
                }

                // Update Harga Terakhir (Polling)
                $stockDataModel->update($stock['id'], [
                    'last_price' => $meta['regularMarketPrice'] ?? $stock['last_price'],
                    'previous_close' => $meta['chartPreviousClose'] ?? $stock['previous_close'],
                    'day_high' => $meta['regularMarketDayHigh'] ?? $stock['day_high'],
                    'day_low' => $meta['regularMarketDayLow'] ?? $stock['day_low'],
                    'price_updated_at' => date('Y-m-d H:i:s')
                ]);

                $needsRefresh = true;
            }
        } catch (\Exception $e) {
            log_message('error', "Yahoo Chart Error [{$code}]: " . $e->getMessage());
        }

        // --- FINAL STEP: RE-SELECT JIKA ADA UPDATE ---
        if ($needsRefresh) {
            $stock = $stockDataModel->select('stock_data.*, emiten.code, emiten.name, emiten.sector, emiten.description, emiten.image, emiten.ai_analysis, emiten.last_ai_update')
                ->join('emiten', 'emiten.id = stock_data.emiten_id')
                ->where('emiten.code', $code)
                ->first();
        }

        // Ambil data sejarah (misal 5 tahun terakhir) untuk View
        $histories = $historyModel->where('emiten_id', $stock['emiten_id'])
            ->orderBy('year', 'DESC')
            ->findAll(5);

        return view('stock_detail', [
            'title' => 'Analisis ' . $code,
            'stock' => $stock,
            'chartData' => $chartData,
            'histories' => $histories
        ]);
    }

    private function scrapeFundamentalIPOT($code)
    {
        $client = \Config\Services::curlrequest();
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

    public function analyze_with_ai($code)
    {
        $emitenModel = new EmitenModel();
        $stockDataModel = new StockDataModel();
        $historyModel = new StockHistoryModel();

        // 1. Ambil data current (JOIN stock_data & emiten)
        // Pastikan revenue dan net_profit ada di tabel stock_data atau di-update saat scraping
        $stock = $stockDataModel->select('stock_data.*, emiten.id as emiten_id, emiten.name, emiten.sector, emiten.description')
            ->join('emiten', 'emiten.id = stock_data.emiten_id')
            ->where('emiten.code', $code)
            ->first();

        if (!$stock) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'Emiten tidak ditemukan']);
        }

        // Ambil data history (4 tahun terakhir: 2024, 2023, 2022, 2021)
        $histories = $historyModel->getTrendByEmiten($stock['emiten_id'], 4);

        // 2. KONSTRUKSI PROMPT
        $prompt = "Kamu adalah analis saham senior dari Bursa Efek Indonesia (BEI). Berikan analisis profesional, tajam, dan objektif untuk emiten berikut:\n\n";
        $prompt .= "DATA EMITEN:\n";
        $prompt .= "- Nama: {$stock['name']} ({$code})\n";
        $prompt .= "- Sektor: {$stock['sector']}\n";
        $prompt .= "- Harga Terakhir: IDR " . number_format($stock['last_price'], 0, ',', '.') . "\n\n";

        $prompt .= "--- TREN KINERJA & RASIO (TAHUNAN) ---\n";
        $prompt .= "Catatan: Revenue/Profit dalam Triliun (T). PBV, PER, DER dalam kali (x).\n\n";

        // A. Masukkan Data Estimasi/Berjalan 2025 (Kondisi Sekarang)
        $prompt .= "TAHUN 2025 (Estimasi/Current):\n";
        $prompt .= " > Revenue: " . ($stock['revenue'] ?? 'N/A') . " T\n";
        $prompt .= " > Net Profit: " . ($stock['net_profit'] ?? 'N/A') . " T\n";
        $prompt .= " > Profitabilitas: ROE {$stock['roe']}%\n";
        $prompt .= " > Valuasi: PBV {$stock['pbv']}x, PER {$stock['per']}x\n";
        $prompt .= " > Risiko: DER {$stock['der']}x\n";
        $prompt .= " > Dividen: IDR " . number_format($stock['dividend'] ?? 0, 0, ',', '.') . " (Yield: " . number_format($stock['dividend_yield'] ?? 0, 2) . "%)\n\n";

        // B. Masukkan Data Sejarah (2024 ke bawah)
        if (!empty($histories)) {
            foreach ($histories as $h) {
                $prompt .= "TAHUN {$h['year']} (Full Year):\n";
                $prompt .= " > Revenue: {$h['revenue']} T\n";
                $prompt .= " > Net Profit: {$h['net_profit']} T\n";
                $prompt .= " > Profitabilitas: ROE {$h['roe']}%, EPS IDR {$h['eps']}\n";
                $prompt .= " > Valuasi: PBV {$h['pbv']}x, PER {$h['per']}x\n";
                $prompt .= " > Risiko: DER {$h['der']}x\n\n";
            }
        }

        $prompt .= "INSTRUKSI ANALISIS:\n";
        $prompt .= "1. Analisis Tren: Bandingkan kinerja 2025 (estimasi) terhadap 2024 dan tahun sebelumnya. Apakah Revenue/Profit tumbuh (YoY)?\n";
        $prompt .= "2. Evaluasi Valuasi: Apakah PBV {$stock['pbv']}x saat ini lebih mahal atau lebih murah dibanding rata-rata historisnya?\n";
        $prompt .= "3. Keamanan Keuangan: Analisis apakah DER {$stock['der']}x menunjukkan risiko utang yang meningkat.\n";
        $prompt .= "4. Kesimpulan: Berikan rekomendasi (Strong Buy / Buy / Hold / Sell) dengan alasan yang logis.\n\n";
        $prompt .= "Format output: Markdown. Gunakan Bahasa Indonesia yang profesional.";

        // 3. Konfigurasi Ollama
        $url = "http://localhost:11434/api/chat";
        $payload = [
            "model" => "deepseek-v3.1:671b-cloud",
            "messages" => [
                ["role" => "user", "content" => $prompt]
            ],
            "stream" => false,
            "options" => [
                "temperature" => 0.3,
                "num_predict" => 2000
            ]
        ];

        $client = \Config\Services::curlrequest();

        try {
            $response = $client->setBody(json_encode($payload))
                ->setHeader('Content-Type', 'application/json')
                ->request('POST', $url, ['timeout' => 120]);

            $result = json_decode($response->getBody(), true);
            $aiText = $result['message']['content'] ?? "Gagal mendapatkan respon AI.";

            $emitenModel->update($stock['emiten_id'], [
                'ai_analysis' => $aiText,
                'last_ai_update' => date('Y-m-d H:i:s')
            ]);

            return $this->response->setJSON(['status' => 'success', 'analysis' => $aiText]);

        } catch (\Exception $e) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Inisialisasi daftar 700+ emiten Indonesia dari JSON
     */
    /**
     * Inisialisasi daftar emiten Indonesia dari JSON
     * Disesuaikan dengan struktur Dual Timestamps (Price & Fundamental)
     */
    private function initializeAllStocks()
    {
        // Tingkatkan limit waktu karena operasi I/O dan DB untuk 200 baris lumayan berat
        set_time_limit(300);

        $emitenModel = new EmitenModel();
        $stockDataModel = new StockDataModel();

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
                $id = $emitenModel->insert([
                    'code' => $s['code'],
                    'name' => $s['name'],
                    'sector' => $s['sector'] ?? 'Unknown'
                ]);

                // 3. Buat baris di stock_data
                // Set kedua timestamp ke tahun 2000 agar dianggap 'expired' oleh fetcher
                $stockDataModel->insert([
                    'emiten_id' => $id,
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