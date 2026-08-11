<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;


class CheckoutController extends Controller
{
    public function checkout(Request $request)
{
    $user = auth()->user();

    $cart = Cart::with('items.product')
        ->where('user_id', $user->id)
        ->first();

    if (!$cart || $cart->items->count() == 0) {

        return response()->json([
            'message' => 'El carrito está vacío.'
        ],400);
    }

    DB::beginTransaction();

    try {

        $total = 0;

        foreach($cart->items as $item){

            $product = Product::find($item->product_id);

            if($product->stock < $item->quantity){

                DB::rollBack();

                return response()->json([
                    'message'=>"No hay suficiente inventario para {$product->name}"
                ],400);
            }

            $total += $product->price * $item->quantity;
        }

        $order = Order::create([
            'user_id'=>$user->id,
            'total'=>$total,
            'status'=>'pendiente'
        ]);

        foreach($cart->items as $item){

            $product = Product::find($item->product_id);

            OrderItem::create([
                'order_id'=>$order->id,
                'product_id'=>$product->id,
                'quantity'=>$item->quantity,
                'price'=>$product->price,
                'subtotal' => $product->price * $item->quantity
            ]);

            $product->stock -= $item->quantity;
            $product->save();
        }

        $cart->items()->delete();

        DB::commit();

        return response()->json([
            'message'=>'Compra realizada correctamente.',
            'order'=>$order
        ]);

    }catch(\Exception $e){

        DB::rollBack();

        return response()->json([
            'message'=>$e->getMessage()
        ],500);

    }

}
}
