<?php

namespace App\Controllers;

use App\Models\EmitenModel;
use App\Models\StockDataModel;
use App\Models\StockHistoryModel;
use App\Models\StockAnalysisModel; // Tambahkan ini
use App\Models\UserModel;          // Tambahkan ini

class AIAnalysisController extends BaseController
{
    public function analyze($code)
    {
        // 1. Inisialisasi Model & Cek Login
        $userId = session()->get('user_id');
        if (!$userId) {
            return $this->response->setStatusCode(401)->setJSON(['status' => 'error', 'message' => 'Silakan login terlebih dahulu']);
        }

        $analysisModel = new StockAnalysisModel();
        $userModel = new UserModel();
        $stockDataModel = new StockDataModel();
        $historyModel = new StockHistoryModel();
        $client = \Config\Services::curlrequest();

        // 2. LOGIKA EFISIENSI: Cek apakah user sudah menganalisis saham ini dalam 24 jam terakhir
        $recentAnalysis = $analysisModel->checkRecentAnalysis($userId, $code);
        if ($recentAnalysis) {
            return $this->response->setJSON([
                'status' => 'success',
                'is_cached' => true,
                'analysis' => $recentAnalysis->analysis_content
            ]);
        }

        // 3. CEK SALDO: Pastikan token cukup
        $user = $userModel->find($userId);
        if (!$user || $user->token_balance <= 0) {
            return $this->response->setStatusCode(402)->setJSON([
                'status' => 'error',
                'message' => 'Saldo token habis. Silakan top up di menu Token.'
            ]);
        }

        // 4. Ambil Data Konteks (Fundamental & Teknikal)
        $stock = $stockDataModel->select('stock_data.*, emiten.id as emiten_id, emiten.name, emiten.sector, emiten.description')
            ->join('emiten', 'emiten.id = stock_data.emiten_id')
            ->where('emiten.code', $code)
            ->first();

        if (!$stock)
            return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'Emiten tidak ditemukan']);

        // --- Logika Penarikan Data Teknikal (Yahoo Finance) ---
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

        // 6. Eksekusi ke DeepSeek (Ollama Lokal)
        $url = env('OLLAMA_URL');
        $apiKey = env('OLLAMA_API_KEY');
        $payload = [
            "model" => "deepseek-v3.2:cloud",
            "messages" => [["role" => "user", "content" => $prompt]],
            "stream" => false,
        ];

        try {
            $response = $client->setBody(json_encode($payload))
                ->setHeader('Content-Type', 'application/json')
                ->setHeader('Authorization', 'Bearer ' . $apiKey)
                ->request('POST', $url, ['timeout' => 180, 'verify' => false]);

            $result = json_decode($response->getBody(), true);
            $aiText = $result['message']['content'] ?? "Gagal mendapatkan respon AI.";

            // GATEKEEPER: Cek validitas konten AI
            // Jangan masuk ke database transaction jika AI tidak memberikan jawaban berguna
            if ($aiText === "Gagal mendapatkan respon AI." || strlen($aiText) < 50) {
                return $this->response->setStatusCode(502)->setJSON([
                    'status' => 'error',
                    'message' => 'AI gagal memberikan analisis yang valid. Saldo Anda tidak terpotong.'
                ]);
            }

            // DATABASE TRANSACTION
            $db = \Config\Database::connect();
            $db->transStart();

            // Simpan hasil analisis ke tabel baru (stock_analyses)
            $analysisModel->insert([
                'user_id' => $userId,
                'ticker' => $code,
                'analysis_content' => $aiText,
                'sentiment' => str_contains($aiText, 'Buy') ? 'Bullish' : (str_contains($aiText, 'Sell') ? 'Bearish' : 'Neutral'),
                'token_spent' => 1,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            // POTONG TOKEN (Pastikan method ini melempar error jika saldo tidak cukup/gagal)
            $userModel->deductToken($userId, 1);

            $db->transComplete();

            // AMBIL SALDO TERBARU (Agar sinkron dengan UI)
            $updatedUser = $userModel->find($userId);

            if ($db->transStatus() === false) {
                return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => 'Gagal memproses transaksi token.']);
            }

            return $this->response->setJSON([
                'status' => 'success',
                'analysis' => $aiText,
                'remaining_tokens' => $updatedUser->token_balance // Gunakan data asli DB
            ]);

        } catch (\Exception $e) {
            log_message('error', "AI Analysis Exception: " . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
            ]);
        }
    }
}