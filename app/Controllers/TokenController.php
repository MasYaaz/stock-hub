<?php
namespace App\Controllers;

use App\Models\UserModel;
use App\Models\TokenPackageModel;
use App\Models\TokenTransactionModel;

class TokenController extends BaseController
{
    public function index()
    {
        $packageModel = new TokenPackageModel();
        $userModel = new UserModel();

        $data = [
            'title' => 'Isi Ulang Token AI',
            'packages' => $packageModel->findAll(),
            'user' => $userModel->find(session()->get('user_id'))
        ];

        return view('token/index', $data);
    }

    /**
     * Simulasi Pembelian Token
     * Nanti di sini tempat integrasi Midtrans/Payment Gateway
     */
    public function buy($packageId)
    {
        $packageModel = new TokenPackageModel();
        $transactionModel = new TokenTransactionModel();
        $userModel = new UserModel();

        $package = $packageModel->find($packageId);
        $userId = session()->get('user_id');

        if (!$package)
            return redirect()->back()->with('error', 'Paket tidak ditemukan.');

        // 1. Catat Transaksi (Status Success untuk simulasi)
        $transactionData = [
            'user_id' => $userId,
            'package_id' => $package->id,
            'amount_paid' => $package->price,
            'status' => 'success'
        ];

        $transactionModel->insert($transactionData);

        // 2. Tambah Saldo Token User
        $userModel->addToken($userId, $package->token_amount);

        return redirect()->to('/stock/token')->with('success', "Berhasil membeli {$package->package_name}!");
    }

    public function history()
    {
        $transactionModel = new TokenTransactionModel();
        $data = [
            'title' => 'Riwayat Transaksi',
            'history' => $transactionModel->getHistory(session()->get('user_id'))
        ];
        return view('token/history', $data);
    }
}