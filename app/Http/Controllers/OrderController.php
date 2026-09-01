<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Table;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    /**
     * POST /api/validate-qr
     */
    public function validateTableQr(Request $request)
    {
        $qrToken = trim($request->input('qr_token', ''));

        if (empty($qrToken)) {
            return response()->json([
                'success' => false,
                'message' => 'Token QR tidak boleh kosong.'
            ], 400);
        }

        $parts = explode('|', $qrToken);
        $rawTable = $parts[0] ?? ''; 
        $secretKey = $parts[1] ?? '';

        $tableNumber = str_replace(['MEJA-', 'meja-'], '', $rawTable);

        $table = Table::where('table_number', $tableNumber)
            ->when(!empty($secretKey), function ($query) use ($secretKey) {
                return $query->where('secret_key', $secretKey);
            })
            ->first();

        if (!$table) {
            $table = Table::where('table_number', $qrToken)->first();
        }

        if (!$table) {
            return response()->json([
                'success' => false,
                'message' => 'QR Code tidak valid atau meja tidak ditemukan.'
            ], 404);
        }

        $activeOrder = Order::where('table_number', $table->table_number)
            ->whereIn('status', ['pending', 'processing', 'preparing'])
            ->latest()
            ->first();

        return response()->json([
            'success'      => true,
            'message'      => 'QR Code berhasil divalidasi.',
            'table_number' => $table->table_number,
            'order_id'     => $activeOrder ? $activeOrder->id : null,
        ]);
    }

    /**
     * POST /api/orders
     */
    public function createOrder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'table_number' => 'required',
            'items'        => 'required|array|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Data pesanan tidak lengkap.',
                'errors'  => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $totalPrice = 0;
            $itemsPayload = $request->items;

            // 1. Hitung total harga pesanan
            foreach ($itemsPayload as $item) {
                $qty   = (int) ($item['quantity'] ?? $item['qty'] ?? 1);
                $price = (float) ($item['price'] ?? 0);
                $totalPrice += ($price * $qty);
            }

            // 2. Generate Nomor Pesanan Otomatis
            $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

            // Validasi payment_method agar sesuai ENUM DB ('cash', 'qris', 'unpaid')
            $allowedPaymentMethods = ['cash', 'qris', 'unpaid'];
            $paymentMethod = $request->payment_method;
            if (!in_array($paymentMethod, $allowedPaymentMethods)) {
                $paymentMethod = 'unpaid';
            }

            // 3. Simpan Pesanan Utama (tabel orders)
            $order = Order::create([
                'order_number'   => $orderNumber,
                'table_number'   => (string) $request->table_number,
                'customer_name'  => $request->customer_name ?? 'Pelanggan',
                'total_price'    => $totalPrice,
                'status'         => 'pending',
                'payment_status' => 'unpaid',
                'payment_method' => $paymentMethod,
            ]);

            // 4. Simpan Item Pesanan (tabel order_items)
            foreach ($itemsPayload as $item) {
                $pId   = $item['product_id'] ?? $item['id'] ?? null;
                $pName = $item['product_name'] ?? $item['name'] ?? null;

                // Fallback pencarian nama produk jika null
                if (empty($pName) && !empty($pId)) {
                    $product = Product::find($pId);
                    $pName = $product ? $product->name : ('Produk #' . $pId);
                }

                // Jika nama masih tetap kosong, berikan fallback nama generik
                if (empty($pName)) {
                    $pName = 'Menu Warung';
                }

                OrderItem::create([
                    'order_id'     => $order->id,
                    'product_id'   => $pId,
                    'product_name' => $pName,
                    'price'        => (float) ($item['price'] ?? 0),
                    'quantity'     => (int) ($item['quantity'] ?? $item['qty'] ?? 1),
                ]);
            }

            DB::commit();

            return response()->json([
                'success'      => true,
                'message'      => 'Pesanan berhasil dibuat!',
                'order_id'     => $order->id,
                'order_number' => $order->order_number,
                'order'        => $order
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan pesanan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/orders/batch
     */
    public function getMultipleOrders(Request $request)
    {
        $ids = $request->query('ids');

        if (!$ids) {
            return response()->json(['success' => false, 'orders' => []]);
        }

        $idArray = is_array($ids) ? $ids : explode(',', $ids);
        
        $orders = Order::with('items')
            ->whereIn('id', $idArray)
            ->latest()
            ->get()
            ->map(function ($order) {
                return [
                    'id'           => $order->id,
                    'order_number' => $order->order_number,
                    'table_number' => (string) $order->table_number,
                    'customer_name'=> $order->customer_name,
                    'total_price'  => (float) $order->total_price,
                    'status'       => $order->status,
                    'items'        => $order->items,
                    'created_at'   => $order->created_at,
                ];
            });

        return response()->json([
            'success' => true,
            'orders'  => $orders
        ]);
    }

    /**
     * GET /api/orders/{id}
     */
public function show($id)
{
    // Pastikan relasi items dan product dimuat
    $order = Order::with(['items.product'])->find($id);

    if (!$order) {
        return response()->json([
            'status' => 'error',
            'message' => 'Pesanan tidak ditemukan'
        ], 404);
    }

    return response()->json([
        'status' => 'success',
        'data' => $order
    ]);
}

    /**
     * GET /api/orders/{id}/status
     */
    public function getOrderStatus($id)
    {
        $order = Order::with('items')->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Pesanan tidak ditemukan.'
            ], 404);
        }

        $ordersAhead = Order::whereIn('status', ['pending', 'processing', 'preparing'])
            ->where('id', '<', $order->id)
            ->count();

        $queuePosition = in_array($order->status, ['completed', 'cancelled']) 
            ? 0 
            : $ordersAhead + 1;

        return response()->json([
            'success'        => true,
            'order'          => $order,
            'queue_position' => $queuePosition,
            'orders_ahead'   => $ordersAhead
        ]);
    }

    /**
     * PATCH /api/orders/{id}/payment
     */
    public function selectPaymentMethod(Request $request, $id)
    {
        $request->validate([
            'payment_method' => 'required|string',
        ]);

        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Pesanan tidak ditemukan.'
            ], 404);
        }

        $order->payment_method = $request->payment_method;
        $order->save();

        return response()->json([
            'success' => true,
            'message' => 'Metode pembayaran berhasil diubah.',
            'order'   => $order
        ]);
    }

    /**
     * PATCH /api/orders/{id}/cancel
     */
    public function cancelOrder($id)
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Pesanan tidak ditemukan.'
            ], 404);
        }

        if ($order->status === 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Pesanan yang sudah selesai tidak dapat dibatalkan.'
            ], 400);
        }

        $order->status = 'cancelled';
        $order->save();

        return response()->json([
            'success' => true,
            'message' => 'Pesanan berhasil dibatalkan.',
            'order'   => $order
        ]);
    }

    /**
     * GET /api/kitchen/orders
     */
public function getKitchenOrders()
{
    $orders = Order::with(['items.product'])
        ->whereIn('status', ['pending', 'cooking', 'processing', 'completed'])
        ->orderBy('created_at', 'desc')
        ->get();

    return response()->json([
        'status' => 'success',
        'data' => $orders
    ]);
} /**
     * PATCH /api/orders/{id}/update-status
     */
    public function updateOrderStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string',
        ]);

        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Pesanan tidak ditemukan.'
            ], 404);
        }

        $order->status = $request->status;
        
        if ($request->status === 'completed') {
            $order->payment_status = 'paid';
        }

        $order->save();

        return response()->json([
            'success' => true,
            'message' => 'Status pesanan berhasil diperbarui.',
            'order'   => $order
        ]);
    }
}