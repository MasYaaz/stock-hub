<?php
namespace App\Controllers;

use App\Models\EmitenModel;
use App\Models\StockHistoryModel;
use App\Models\StockAnalysisModel;

class StockDetailController extends BaseController
{
    public function detail($code)
    {
        $emitenModel = new EmitenModel();
        $historyModel = new StockHistoryModel();
        $analysisModel = new StockAnalysisModel(); // Tambahkan model analisis

        // 1. Ambil data awal dari DB
        // CATATAN: ai_analysis dan last_ai_update sudah dihapus dari select karena pindah tabel
        $stock = $emitenModel->where('code', $code)->first();

        if (!$stock) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $now = time();
        $needsRefresh = false;

        // --- LOGIKA 1: Scrapping Data ---
        $lastFundamentalUpdate = strtotime($stock['fundamental_updated_at'] ?? '2000-01-01');

        if (($now - $lastFundamentalUpdate) > 86400) {
            try {
                // Scrape IDN (Profil, Harga, Market Cap, Dividen)
                $profileData = $this->scrapeProfileGoogle($code);
                // Scrape IPOT (History Fundamental)
                $scrapedHistory = $this->scrapeFundamentalIPOT($code);
                // Ambil Beta dari MarketWatch (Karena di IDN tidak ada)
                $beta = $this->scrapeBetaFromMarketWatch($code);

                // // --- LOGGING HASIL SCRAPPING (CEK DI writable/logs/) ---
                // log_message('info', "==== START SCRAPE LOG [{$code}] ====");

                // log_message('info', "IDNFinancials Profile Data: " . json_encode($profileData, JSON_PRETTY_PRINT));

                // if ($scrapedHistory) {
                //     log_message('info', "IPOT Current Data: " . json_encode($scrapedHistory['current'], JSON_PRETTY_PRINT));
                //     log_message('info', "IPOT History Data: " . json_encode($scrapedHistory['history'], JSON_PRETTY_PRINT));
                // } else {
                //     log_message('error', "IPOT Scrape returned NULL for {$code}");
                // }

                // log_message('info', "MarketWatch Beta: " . ($beta ?? 'NULL'));

                // log_message('info', "==== END SCRAPE LOG [{$code}] ====");

                if ($scrapedHistory && $profileData) {
                    // Hitung Yield
                    $divYield = ($profileData['last_dividend'] > 0 && $profileData['price'] > 0)
                        ? ($profileData['last_dividend'] / $profileData['price']) * 100 : 0;

                    // Update Emiten (Statis)
                    $emitenModel->update($stock['id'], [
                        'description' => $profileData['description'] ?? $stock['description'],
                        'last_price' => $profileData['price'],
                        'market_cap' => $profileData['market_cap'],
                        'dividend' => $profileData['last_dividend'],
                        'dividend_yield' => $divYield,
                        'beta' => $beta ?? $stock['beta'], // Update Beta jika ditemukan
                        'pbv' => $scrapedHistory['current']['pbv'] ?? $stock['pbv'],
                        'per' => $scrapedHistory['current']['per'] ?? $stock['per'],
                        'roe' => $scrapedHistory['current']['roe'] ?? $stock['roe'],
                        'der' => $scrapedHistory['current']['der'] ?? $stock['der'],
                        'fundamental_updated_at' => date('Y-m-d H:i:s')
                    ]);

                    // Update Sejarah Multi-Tahun
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

                            $existing = $historyModel->where([
                                'emiten_id' => $stock['id'],
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

        // 2. Refresh data jika fundamental baru saja di-update
        if ($needsRefresh)
            $stock = $emitenModel->find($stock['id']);


        // 3. Ambil 5 riwayat tahunan terakhir
        $histories = $historyModel->where('emiten_id', $stock['id'])
            ->orderBy('year', 'DESC')
            ->findAll(5);

        // 4. Cek Analisis AI Terakhir (Optional: Menampilkan analisis terakhir milik user tersebut)
        $lastAnalysis = null;
        if (session()->get('user_id')) {
            $lastAnalysis = $analysisModel->where('user_id', session()->get('user_id'))
                ->where('ticker', $code)
                ->orderBy('created_at', 'DESC')
                ->first();
        }

        return view('stock_detail', [
            'title' => 'Detail ' . $code,
            'stock' => $stock,
            'histories' => $histories,
            'last_analysis' => $lastAnalysis // Kirim hasil AI ke view
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

    private function scrapeProfileGoogle($code)
    {
        $client = \Config\Services::curlrequest();
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
        $client = \Config\Services::curlrequest();
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