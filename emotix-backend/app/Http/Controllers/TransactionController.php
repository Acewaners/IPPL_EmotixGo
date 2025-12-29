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
        $transaction = Transaction::findOrFail($id);

        // 1. Validasi Status (SESUAIKAN DENGAN FRONTEND)
        $r->validate([
            // Kita pakai: pending, processing, shipped, completed, cancelled
            'status'          => 'required|in:pending,processing,shipped,completed,cancelled',
            'tracking_number' => 'nullable|max:100',
        ]);

        $userId   = $r->user()->user_id ?? null;
        $isSeller = $userId === $transaction->seller_id;
        $isBuyer  = $userId === $transaction->buyer_id;

        // 2. Cek Hak Akses
        abort_unless($isSeller || $isBuyer, 403, 'Not allowed.');

        $oldStatus = $transaction->status;

        // 3. Logika Khusus Buyer (Opsional: Misal buyer konfirmasi terima barang)
        if ($isBuyer) {
            // Buyer hanya boleh ubah jadi 'completed' (konfirmasi terima)
            if ($r->input('status') !== 'completed') {
                abort(403, 'Buyer can only mark order as completed.');
            }
            $transaction->update(['status' => 'completed']);
        } 
        // 4. Logika Seller (Bebas ubah apa saja)
        else {
            $transaction->update($r->only('status', 'tracking_number'));
        }

        // ⭐ 5. FITUR PENTING: Tambah Counter 'Sold' di Produk
        // Hanya jalan jika status berubah menjadi 'completed'
        if ($oldStatus !== 'completed' && $transaction->status === 'completed') {
            $transaction->loadMissing('details.product');

            foreach ($transaction->details as $detail) {
                if ($detail->product) {
                    // Tambah jumlah terjual sesuai quantity pesanan
                    $detail->product->increment('sold', $detail->quantity);
                    
                    // Opsional: Kurangi stok real (jika belum dikurangi di awal)
                    // $detail->product->decrement('stock', $detail->quantity);
                }
            }
        }

        return response()->json([
            'message' => 'Status updated successfully',
            'data' => $transaction->load('details.product')
        ]);
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
