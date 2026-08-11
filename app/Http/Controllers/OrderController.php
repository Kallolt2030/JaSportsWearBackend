<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Historial del usuario autenticado
     */
    public function myOrders(Request $request)
    {
        $orders = Order::with('items.product')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json($orders);
    }

    /**
     * Todas las órdenes (Administrador)
     */
    public function index()
    {
        $orders = Order::with([
            'user',
            'items.product'
        ])->latest()->get();

        return response()->json($orders);
    }

    /**
     * Ver una orden
     */
    public function show(Order $order)
    {
        return response()->json(
            $order->load([
                'user',
                'items.product'
            ])
        );
    }

    /**
     * Actualizar estado
     */
    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|in:pendiente,pagado,enviado,entregado,cancelado'
        ]);

        $order->status = $request->status;
        $order->save();

        return response()->json([
            'message' => 'Estado actualizado',
            'order' => $order
        ]);
    }
}