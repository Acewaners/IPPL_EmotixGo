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
                'buyer_id'    => $r->user()->user_id,
                'seller_id'   => $data['seller_id'],
                'total_price' => $total,
                'status'           => 'processing',
                'transaction_date' => now(),
                'created_at'       => now(),
            ]);

            foreach($details as $d){
                TransactionDetail::create(['transaction_id'=>$trx->transaction_id] + $d);
            }

            return $trx->load('details.product');
        });
    }

    public function indexBuyer(Request $r){
        return Transaction::where('buyer_id',$r->user()->user_id)
            ->with(['details.product', 'seller'])
            ->latest('transaction_date')
            ->paginate(10);
    }

    public function indexSeller(Request $r){
        return Transaction::where('seller_id',$r->user()->user_id)
            ->with(['details.product', 'buyer'])
            ->latest('transaction_date')
            ->paginate(10);
    }

    public function updateStatus(Request $r, $id)
    {
        $transaction = Transaction::findOrFail($id);

        $r->validate([
            'status'          => 'required|in:pending_payment,processing,shipped,completed,failed,cancelled',
            'tracking_number' => 'nullable|max:100',
        ]);

        $user = $r->user();
        $userId = $user->user_id;

        $isSeller = $userId === $transaction->seller_id;
        $isBuyer  = $userId === $transaction->buyer_id;
        $isAdmin  = ($user->role === 'admin') || ($user->is_admin == 1);

        abort_unless($isSeller || $isBuyer || $isAdmin, 403);

        $oldStatus = $transaction->status;
        $requestedStatus = $r->input('status');

        // --- LOGIKA UTAMA PERBAIKAN ---
        if ($isBuyer && !$isAdmin) {

            if ($requestedStatus === 'processing') {
                if (in_array($transaction->status, ['pending_payment', 'processing'])) {
                    $transaction->update(['status' => 'processing']);
                    return $transaction->load(['details.product', 'buyer']);
                }
            }
            elseif ($requestedStatus === 'completed') {
                $transaction->update(['status' => 'completed']);
            }
            else {
                abort(403, 'Buyer can only pay (processing) or mark as completed.');
            }

        } else {
            $transaction->update($r->only('status', 'tracking_number'));
        }

        if ($oldStatus !== 'completed' && $transaction->status === 'completed') {
            $transaction->loadMissing('details.product');
            foreach ($transaction->details as $detail) {
                if ($detail->product) {
                    $detail->product->increment('sold', $detail->quantity);
                }
            }
        }

        return $transaction->load(['details.product', 'buyer']);
    }

    public function show(Request $r, Transaction $transaction)
    {
        abort_unless(
            $r->user()->user_id === $transaction->buyer_id ||
            $r->user()->user_id === $transaction->seller_id,
            403
        );

        return $transaction->load('details.product');
    }

}
