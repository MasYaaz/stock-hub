<?php
namespace App\Controllers;

use App\Models\EmitenModel;
use App\Models\StockHistoryModel;
use App\Models\StockAnalysisModel;
use App\Libraries\StockFetcher;

class StockDetailController extends BaseController
{
    public function detail($code)
    {
        $emitenModel = new EmitenModel();
        $historyModel = new StockHistoryModel();
        $analysisModel = new StockAnalysisModel();
        $stockFetcher = new StockFetcher(); // Load Library

        $stock = $emitenModel->where('code', $code)->first();

        if (!$stock) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        // Cek refresh fundamental (1 hari sekali)
        $lastUpdate = strtotime($stock['fundamental_updated_at'] ?? '2000-01-01');
        if ((time() - $lastUpdate) > 86400) {
            $stockFetcher->updateFundamental($code);
            // Ambil data terbaru setelah update
            $stock = $emitenModel->find($stock['id']);
        }

        $histories = $historyModel->where('emiten_id', $stock['id'])
            ->orderBy('year', 'DESC')
            ->findAll(5);

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
            'last_analysis' => $lastAnalysis
        ]);
    }
}