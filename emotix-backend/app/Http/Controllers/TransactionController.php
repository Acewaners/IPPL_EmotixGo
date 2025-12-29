<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    public function create(Request $r){
        $data = $r->validate([
            'seller_id'=>'required|integer|exists:users,user_id',
            'items'=>'required|array|min:1',
            'items.*.product_id'=>'required|integer|exists:products,product_id',
            'items.*.quantity'=>'required|integer|min:1',
        ]);

        return DB::transaction(function() use ($r,$data){
            $total = 0; $details = [];
            foreach($data['items'] as $it){
                $p = Product::lockForUpdate()->findOrFail($it['product_id']);
                abort_if($p->stock < $it['quantity'], 422, 'Stock not enough');
                $sub = $p->price * $it['quantity'];
                $total += $sub;
                $p->decrement('stock',$it['quantity']);
                $details[] = ['product_id'=>$p->product_id,'quantity'=>$it['quantity'],'subtotal'=>$sub];
            }
            $trx = Transaction::create([
                'buyer_id'=>$r->user()->user_id,
                'seller_id'=>$data['seller_id'],
                'total_price'=>$total,
                'status'=>'pending_payment',
                'created_at'=>now(),
            ]);
            foreach($details as $d){
                TransactionDetail::create(['transaction_id'=>$trx->transaction_id] + $d);
            }
            return $trx->load('details.product');
        });
    }

    public function indexBuyer(Request $r){
        return Transaction::where('buyer_id',$r->user()->user_id)
            ->with('details.product')->latest('transaction_date')->paginate(10);
    }

    public function indexSeller(Request $r){
        return Transaction::where('seller_id',$r->user()->user_id)
            ->with('details.product')->latest('transaction_date')->paginate(10);
    }

    public function updateStatus(Request $r, $id)
    {
        // 1. Cari transaksi secara manual (lebih aman dari error binding)
        $transaction = Transaction::findOrFail($id);

        // 2. Validasi Status (Sesuaikan dengan frontend Anda)
        // Tambahkan 'cancelled' jika frontend mengirim status itu
        $r->validate([
            'status'          => 'required|in:pending_payment,processing,shipped,completed,failed,cancelled',
            'tracking_number' => 'nullable|max:100',
        ]);

        $user = $r->user();
        $userId = $user->user_id; // Pastikan primary key user Anda 'user_id'

        // 3. Cek Kepemilikan & Role
        $isSeller = $userId === $transaction->seller_id;
        $isBuyer  = $userId === $transaction->buyer_id;
        // Cek apakah user adalah admin (sesuai kolom di database Anda, misal 'role' atau 'is_admin')
        $isAdmin  = ($user->role === 'admin') || ($user->is_admin == 1); 

        // 4. Logika Izin: Izinkan jika Seller, Buyer, ATAU Admin
        abort_unless($isSeller || $isBuyer || $isAdmin, 403, 'Not allowed. You are not the owner of this order.');

        $oldStatus = $transaction->status;

        // 5. Logika Update
        if ($isBuyer && !$isAdmin) {
            // Buyer hanya boleh konfirmasi selesai
            if ($r->input('status') !== 'completed') {
                abort(403, 'Buyer can only mark order as completed.');
            }
            $transaction->update(['status' => 'completed']);
        } else {
            // Seller atau Admin bebas ubah status apa saja
            $transaction->update($r->only('status', 'tracking_number'));
        }

        // 6. Logika Tambah "Sold" (Terjual)
        if ($oldStatus !== 'completed' && $transaction->status === 'completed') {
            $transaction->loadMissing('details.product');
            foreach ($transaction->details as $detail) {
                if ($detail->product) {
                    // Pastikan kolom 'sold' ada di tabel products
                    $detail->product->increment('sold', $detail->quantity);
                }
            }
        }

        return $transaction->load(['details.product', 'buyer']);
    }

    public function show(Request $r, Transaction $transaction)
    {
        // buyer ATAU seller boleh lihat
        abort_unless(
            $r->user()->user_id === $transaction->buyer_id ||
            $r->user()->user_id === $transaction->seller_id,
            403
        );

        return $transaction->load('details.product');
    }

}
