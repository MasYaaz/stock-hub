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
            'title' => 'Dashboard BedahSaham',
            'stocks' => $stockDataModel->getStockSummary(),
            'total' => $emitenModel->countAllResults()
        ];
        return view('stock_index', $data);
    }

    public function get_history($symbol, $range = '1d')
    {
        // 1. Set Timezone di awal agar sinkron
        date_default_timezone_set('Asia/Jakarta');

        $ticker = ($symbol === 'IHSG') ? '^JKSE' : strtoupper($symbol) . ".JK";
        $cacheKey = "stock_history_{$symbol}_{$range}";
        $today = date('Y-m-d'); // Tanggal hari ini di Jakarta

        // Hapus baris cache ini saat testing agar perubahan kode langsung terasa
        if ($cachedData = cache($cacheKey)) {
            return $this->response->setJSON($cachedData);
        }

        $end = time();
        // Tentukan rentang waktu & interval
        // Tambahkan padding sekitar 20-30 unit data ekstra di setiap range
        switch ($range) {
            case '1d':
                // Untuk 1D, kita tarik 5 hari agar 20 titik pertama (5m) terpenuhi
                $start = strtotime('-5 days', $end);
                $interval = '5m';
                break;
            case '1w':
                // Untuk 1W, tarik 20 hari agar SMA 20 (30m) punya data awal
                $start = strtotime('-20 days', $end);
                $interval = '30m';
                break;
            case '1m':
                // Tarik 60 hari agar data 1 bulan (1d) terhitung penuh sejak awal
                $start = strtotime('-60 days', $end);
                $interval = '1d';
                break;
            case '6m':
                $start = strtotime('-210 days', $end);
                $interval = '1d';
                break;
            default:
                $start = strtotime('-400 days', $end);
                $interval = '1d';
                break;
        }

        $url = "https://query1.finance.yahoo.com/v8/finance/chart/{$ticker}?period1={$start}&period2={$end}&interval={$interval}";
        $client = \Config\Services::curlrequest();

        try {
            $response = $client->request('GET', $url, [
                'headers' => ['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/122.0.0.0 Safari/537.36'],
                'verify' => false,
                'timeout' => 15
            ]);

            $body = json_decode($response->getBody(), true);
            $result = $body['chart']['result'][0] ?? null;

            if (!$result || !isset($result['timestamp'])) {
                return $this->response->setJSON(['labels' => [], 'prices' => [], 'msg' => 'Data tidak tersedia']);
            }

            // Ambil penutupan terakhir & tanggal data terakhir
            $timestamps = $result['timestamp'];
            $prices = $result['indicators']['quote'][0]['close'] ?? [];

            $allData = [];
            foreach ($timestamps as $key => $ts) {
                if (isset($prices[$key]) && $prices[$key] !== null) {
                    $allData[] = [
                        'ts' => (int) $ts,
                        'price' => (float) $prices[$key],
                        'date_key' => date('Y-m-d', $ts)
                    ];
                }
            }

            if (empty($allData))
                return $this->response->setJSON(['labels' => [], 'prices' => []]);

            $latestEntry = end($allData);
            $latestDateInData = $latestEntry['date_key'];
            $lastPrice = (float) $latestEntry['price'];

            // Filter label dan harga
            $cleanLabels = [];
            $cleanPrices = [];
            if ($range == '1d') {
                foreach ($allData as $data) {
                    if ($data['date_key'] === $latestDateInData) {
                        $cleanLabels[] = date('H:i', $data['ts']);
                        $cleanPrices[] = $data['price'];
                    }
                }
            } else {
                foreach ($allData as $data) {
                    $cleanLabels[] = ($range == '1w') ? date('d M H:i', $data['ts']) : date('d M y', $data['ts']);
                    $cleanPrices[] = $data['price'];
                }
            }

            // LOGIKA PENENTUAN REFERENCE
            $stockDataModel = new StockDataModel();
            $emitenModel = new EmitenModel();
            $referencePrice = 0;

            if ($range == '1d') {
                // CEK APAKAH MARKET LIBUR (Tanggal data terakhir bukan hari ini)
                if ($latestDateInData !== $today) {
                    // MARKET LIBUR: Reference disamakan dengan Last Price agar Change = 0
                    $referencePrice = $lastPrice;
                } else {
                    // MARKET BUKA: Ambil Previous Close dari DB
                    $emiten = $emitenModel->where('code', $symbol)->first();
                    $prevCloseFromDb = 0;
                    if ($emiten) {
                        $stock = $stockDataModel->where('emiten_id', $emiten['id'])->first();
                        $prevCloseFromDb = (float) ($stock['previous_close'] ?? 0);
                    }
                    $referencePrice = ($prevCloseFromDb > 0) ? $prevCloseFromDb : (float) $cleanPrices[0];
                }
            } else {
                // RANGE 1W, 1M, dst: Pakai harga pertama di dataset
                $referencePrice = (float) $cleanPrices[0];
            }

            $change = $lastPrice - $referencePrice;
            $changePct = ($referencePrice > 0) ? ($change / $referencePrice) * 100 : 0;

            $finalResponse = [
                'symbol' => $symbol,
                'last_price' => $lastPrice,
                'reference_price' => $referencePrice,
                'change_raw' => round($change, 2),
                'change_pct' => round($changePct, 2),
                'last_date' => $latestDateInData,
                'today_server' => $today, // Untuk debug
                'is_market_open' => ($latestDateInData === $today),
                'labels' => $cleanLabels,
                'prices' => $cleanPrices,
            ];

            cache()->save($cacheKey, $finalResponse, 120);
            return $this->response->setJSON($finalResponse);

        } catch (\Exception $e) {
            return $this->response->setStatusCode(500)->setJSON(['error' => $e->getMessage()]);
        }
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

        // 1. Ambil data awal dari DB (Join untuk dapat info emiten)
        $stock = $stockDataModel->select('stock_data.*, emiten.code, emiten.name, emiten.sector, emiten.notation, emiten.description, emiten.image, emiten.ai_analysis, emiten.last_ai_update')
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

        // --- LOGIKA 1: FUNDAMENTAL (FMP + SCRAPING) ---
        // Update data fundamental jika sudah lebih dari 24 jam
        $lastFundamentalUpdate = strtotime($stock['fundamental_updated_at'] ?? '2000-01-01');

        if (($now - $lastFundamentalUpdate) > 86400) {
            try {
                // A. Fetch FMP (Profile)
                $fmpUrl = "{$baseUrl}/profile?symbol={$symbol}&apikey={$apiKey}";
                $resFmp = $client->request('GET', $fmpUrl, ['verify' => false]);
                $bodyFmp = json_decode($resFmp->getBody(), true);
                $fmpData = $bodyFmp[0] ?? null;

                // B. Fetch IndoPremier Scraping
                $scrapedData = $this->scrapeFundamentalIPOT($code);

                if ($fmpData && $scrapedData) {
                    // Hitung Yield
                    $divYield = ($fmpData['lastDividend'] ?? 0) > 0 && ($fmpData['price'] ?? 0) > 0
                        ? ($fmpData['lastDividend'] / $fmpData['price']) * 100
                        : 0;

                    // Update Emiten (Statis)
                    $emitenModel->update($stock['emiten_id'], [
                        'description' => $fmpData['description'] ?? $stock['description'],
                        'image' => $fmpData['image'] ?? $stock['image']
                    ]);

                    // Update Stock Data (Fundamental)
                    $stockDataModel->update($stock['id'], [
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
                    ]);

                    // Update Sejarah Multi-Tahun
                    if (isset($scrapedData['history']) && is_array($scrapedData['history'])) {
                        foreach ($scrapedData['history'] as $year => $values) {
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

                            $existing = $historyModel->where([
                                'emiten_id' => $stock['emiten_id'],
                                'year' => $year,
                                'period' => 'FY'
                            ])->first();

                            $existing ? $historyModel->update($existing['id'], $historyData) : $historyModel->insert($historyData);
                        }
                    }
                    $needsRefresh = true;
                }
            } catch (\Exception $e) {
                log_message('error', "Fundamental Update Error [{$code}]: " . $e->getMessage());
            }
        }

        // --- LOGIKA 2: HARGA ---
        // (Fetch Yahoo dihapus. Data harga diambil dari DB yang di-update background process)

        // 2. Refresh data stock jika ada perubahan fundamental tadi
        if ($needsRefresh) {
            $stock = $stockDataModel->select('stock_data.*, emiten.code, emiten.name, emiten.sector, emiten.notation, emiten.description, emiten.image, emiten.ai_analysis, emiten.last_ai_update')
                ->join('emiten', 'emiten.id = stock_data.emiten_id')
                ->where('emiten.code', $code)
                ->first();
        }

        // Ambil data sejarah untuk ditampilkan di tabel View
        $histories = $historyModel->where('emiten_id', $stock['emiten_id'])
            ->orderBy('year', 'DESC')
            ->findAll(5);

        return view('stock_detail', [
            'title' => 'Detail ' . $code,
            'stock' => $stock,
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
        $client = \Config\Services::curlrequest();

        // 1. Ambil data fundamental (Stock + Emiten)
        $stock = $stockDataModel->select('stock_data.*, emiten.id as emiten_id, emiten.name, emiten.sector,emiten.notation, emiten.description')
            ->join('emiten', 'emiten.id = stock_data.emiten_id')
            ->where('emiten.code', $code)
            ->first();

        if (!$stock)
            return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'Emiten tidak ditemukan']);

        // 2. AMBIL DATA TEKNIKAL (Sama dengan logika Chart di fungsi detail)
        $symbol = $code . ".JK";
        $yahooUrl = "https://query1.finance.yahoo.com/v8/finance/chart/{$symbol}?range=1y&interval=1wk";
        $technicalContext = "";

        try {
            $resYahoo = $client->request('GET', $yahooUrl, ['headers' => ['User-Agent' => 'Mozilla/5.0'], 'verify' => false]);
            $bodyYahoo = json_decode($resYahoo->getBody(), true);
            $prices = $bodyYahoo['chart']['result'][0]['indicators']['quote'][0]['close'] ?? [];

            // Bersihkan data null
            $prices = array_values(array_filter($prices));

            if (count($prices) >= 10) {
                $lastPrice = end($prices);
                $prevPrice = $prices[count($prices) - 2];
                $avgPrice = array_sum(array_slice($prices, -20)) / 20; // MA 20 (Mingguan)
                $highYear = max($prices);
                $lowYear = min($prices);

                $trend = ($lastPrice > $avgPrice) ? "Uptrend" : "Downtrend";
                $performance = (($lastPrice - $prices[0]) / $prices[0]) * 100;

                $technicalContext = "--- DATA TEKNIKAL (Berdasarkan Chart 1 Tahun) ---\n";
                $technicalContext .= "- Trend Harga: {$trend} (Posisi vs MA20 Mingguan)\n";
                $technicalContext .= "- Performa 1 Tahun: " . number_format($performance, 2) . "%\n";
                $technicalContext .= "- Range 52-Minggu: IDR " . number_format($lowYear) . " - " . number_format($highYear) . "\n";
                $technicalContext .= "- Momentum: Harga saat ini (" . number_format($lastPrice) . ") dibandingkan penutupan minggu lalu (" . number_format($prevPrice) . ")\n\n";
            }
        } catch (\Exception $e) {
            log_message('error', "AI Technical Fetch Error: " . $e->getMessage());
        }

        // 3. Ambil data history fundamental
        $histories = $historyModel->getTrendByEmiten($stock['emiten_id'], 4);

        // 4. KONSTRUKSI PROMPT HYBRID (Struktur Lebih Rapi)
        $prompt = "Kamu adalah analis investasi profesional yang ramah. Berikan analisis saham {$code} yang mudah dipahami orang awam.\n\n";

        $prompt .= "--- DATA INPUT ---\n";
        $prompt .= "Emiten: {$stock['name']} ({$stock['sector']})\n";
        $prompt .= "Harga: IDR " . number_format($stock['last_price'], 0, ',', '.') . "\n";
        $prompt .= $technicalContext;
        $prompt .= "Fundamental: PBV {$stock['pbv']}x, ROE {$stock['roe']}%, DER {$stock['der']}x\n\n";

        $prompt .= "--- INSTRUKSI FORMAT OUTPUT (WAJIB) ---\n";
        $prompt .= "Gunakan struktur berikut:\n";
        $prompt .= "1. **Ringkasan Kondisi**: Gunakan bahasa sederhana (misal: 'Perusahaan sedang untung besar' atau 'Hati-hati, harga sudah terlalu mahal').\n";
        $prompt .= "2. **Analisis Kesehatan (Fundamental)**: Jelaskan arti angka PBV/ROE tersebut untuk orang awam.\n";
        $prompt .= "3. **Analisis Pergerakan (Teknikal)**: Jelaskan posisi harga saat ini terhadap riwayat setahun terakhir.\n";
        $prompt .= "4. **Kelebihan & Risiko**: Buat dalam poin-poin.\n";
        $prompt .= "5. **Rekomendasi Final**: Berikan label (Strong Buy / Buy / Hold / Sell) dengan warna indikasi (misal: 🟢 untuk Buy, 🔴 untuk Sell).\n\n";

        $prompt .= "Format: Gunakan Bold, Bullet points, dan Bahasa Indonesia yang santun tapi profesional. Hindari istilah teknis yang terlalu rumit tanpa penjelasan.";

        if (!empty($histories)) {
            $prompt .= "--- TAHUNAN (Histori) ---\n";
            foreach ($histories as $h) {
                $prompt .= "- {$h['year']}: Rev {$h['revenue']}, NetProfit {$h['net_profit']}, PBV {$h['pbv']}x\n";
            }
        }

        $prompt .= "\nINSTRUKSI:\n";
        $prompt .= "1. Evaluasi apakah harga saat ini sudah kemahalan (Pucuk) melihat range 52-minggu.\n";
        $prompt .= "2. Hubungkan tren harga (Uptrend/Downtrend) dengan kesehatan fundamental.\n";
        $prompt .= "3. Berikan rekomendasi: Strong Buy/Buy/Hold/Sell dan alasannya.\n";
        $prompt .= "Format: Markdown, Bahasa Indonesia, Profesional.";

        // 5. Kirim ke Ollama
        $url = "http://localhost:11434/api/chat";
        $payload = [
            "model" => "deepseek-v3.1:671b-cloud",
            "messages" => [["role" => "user", "content" => $prompt]],
            "stream" => false,
            "options" => ["temperature" => 0.2]
        ];

        try {
            $response = $client->setBody(json_encode($payload))
                ->setHeader('Content-Type', 'application/json')
                ->request('POST', $url, ['timeout' => 150]);

            $result = json_decode($response->getBody(), true);
            $aiText = $result['message']['content'] ?? "Gagal mendapatkan respon AI.";

            $emitenModel->update($stock['emiten_id'], ['ai_analysis' => $aiText, 'last_ai_update' => date('Y-m-d H:i:s')]);
            return $this->response->setJSON(['status' => 'success', 'analysis' => $aiText]);
        } catch (\Exception $e) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
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
                    'sector' => $s['sector'] ?? 'Unknown',
                    'notation' => $s['notation']
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