<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Stock;
use App\Models\StockLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Display a listing of all orders.
     */
    public function index()
    {
        $orders = Order::with('orderProducts.product', 'orderProducts.stock')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $orders,
            'count' => $orders->count()
        ]);
    }

    /**
     * Store a newly created order.
     * Decreases stock quantity and creates stock log.
     */
    public function store(Request $request)
    {
        $request->validate([
            'customer_name' => 'required|string|max:255',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.stock_id' => 'required|exists:stocks,id',
            'products.*.quantity' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();
        try {
            // Generate unique invoice number
            $invoiceNumber = $this->generateInvoiceNumber();

            $totalAmount = 0;
            $orderProducts = [];

            // Process each product
            foreach ($request->products as $productData) {
                $stock = Stock::findOrFail($productData['stock_id']);
                $quantity = $productData['quantity'];

                // Check if stock belongs to the product
                if ($stock->product_id != $productData['product_id']) {
                    throw new \Exception("Stock does not belong to the specified product");
                }

                // Check if enough quantity is available
                if ($stock->quantity < $quantity) {
                    throw new \Exception("Insufficient stock. Available: {$stock->quantity}, Requested: {$quantity}");
                }

                // Calculate prices
                $salePrice = $stock->sale_price;
                $subTotal = $salePrice * $quantity;
                $purchasePrice = $stock->purchase_price;
                $profitPercentage = $purchasePrice > 0
                    ? (($salePrice - $purchasePrice) / $purchasePrice) * 100
                    : 0;

                $totalAmount += $subTotal;

                // Store previous quantity for log
                $previousQuantity = $stock->quantity;

                // Decrease stock quantity
                $stock->quantity -= $quantity;
                $stock->last_update_at = now();
                $stock->save();

                // Create stock log
                StockLog::create([
                    'type' => 'Order-create',
                    'stock_id' => $stock->id,
                    'product_id' => $stock->product_id,
                    'previous_quantity' => $previousQuantity,
                    'change_quantity' => -$quantity,
                    'current_quantity' => $stock->quantity,
                ]);

                // Store order product data
                $orderProducts[] = [
                    'product_id' => $productData['product_id'],
                    'stock_id' => $stock->id,
                    'quantity' => $quantity,
                    'sale_price' => $salePrice,
                    'sub_total' => $subTotal,
                    'profit' => round($profitPercentage, 2),
                ];
            }

            // Create order
            $order = Order::create([
                'invoice_number' => $invoiceNumber,
                'date_time' => now(),
                'total_amount' => $totalAmount,
                'customer_name' => $request->customer_name,
                'status' => 'Pending',
            ]);

            // Create order products
            foreach ($orderProducts as $orderProductData) {
                $order->orderProducts()->create($orderProductData);
            }

            DB::commit();

            $order->load('orderProducts.product', 'orderProducts.stock');

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully',
                'data' => $order
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Display the specified order.
     */
    public function show(string $id)
    {
        $order = Order::with('orderProducts.product', 'orderProducts.stock')->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $order
        ]);
    }

    /**
     * Update the specified order.
     * Restores stock quantity, then decreases with new quantities.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'customer_name' => 'sometimes|required|string|max:255',
            'status' => 'sometimes|required|in:Pending,Processing,Delivered,Cancelled',
            'products' => 'sometimes|required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.stock_id' => 'required|exists:stocks,id',
            'products.*.quantity' => 'required|integer|min:1',
        ]);

        $order = Order::with('orderProducts')->findOrFail($id);

        DB::beginTransaction();
        try {
            // Restore stock quantities from existing order products
            foreach ($order->orderProducts as $orderProduct) {
                $stock = Stock::findOrFail($orderProduct->stock_id);
                $previousQuantity = $stock->quantity;


            }

            // If products are being updated
            if ($request->has('products')) {
                // Delete existing order products and restore stock
                foreach ($order->orderProducts as $orderProduct) {
                    $stock = Stock::findOrFail($orderProduct->stock_id);
                    $previousQuantity = $stock->quantity;

                    // Restore stock quantity
                    $restoreQuantity = $orderProduct->quantity ?? 1;
                    $stock->quantity += $restoreQuantity;
                    $stock->last_update_at = now();
                    $stock->save();

                    // Log stock restoration
                    StockLog::create([
                        'type' => 'Order-update',
                        'stock_id' => $stock->id,
                        'product_id' => $stock->product_id,
                        'previous_quantity' => $previousQuantity,
                        'change_quantity' => $restoreQuantity,
                        'current_quantity' => $stock->quantity,
                    ]);
                }

                // Delete old order products
                $order->orderProducts()->delete();

                // Process new products (same as store)
                $totalAmount = 0;
                $orderProducts = [];

                foreach ($request->products as $productData) {
                    $stock = Stock::findOrFail($productData['stock_id']);
                    $quantity = $productData['quantity'];

                    if ($stock->product_id != $productData['product_id']) {
                        throw new \Exception("Stock does not belong to the specified product");
                    }

                    if ($stock->quantity < $quantity) {
                        throw new \Exception("Insufficient stock. Available: {$stock->quantity}, Requested: {$quantity}");
                    }

                    $salePrice = $stock->sale_price;
                    $subTotal = $salePrice * $quantity;
                    $purchasePrice = $stock->purchase_price;
                    $profitPercentage = $purchasePrice > 0
                        ? (($salePrice - $purchasePrice) / $purchasePrice) * 100
                        : 0;

                    $totalAmount += $subTotal;

                    $previousQuantity = $stock->quantity;
                    $stock->quantity -= $quantity;
                    $stock->last_update_at = now();
                    $stock->save();

                    StockLog::create([
                        'type' => 'Order-update',
                        'stock_id' => $stock->id,
                        'product_id' => $stock->product_id,
                        'previous_quantity' => $previousQuantity,
                        'change_quantity' => -$quantity,
                        'current_quantity' => $stock->quantity,
                    ]);

                    $orderProducts[] = [
                        'product_id' => $productData['product_id'],
                        'stock_id' => $stock->id,
                        'quantity' => $quantity,
                        'sale_price' => $salePrice,
                        'sub_total' => $subTotal,
                        'profit' => round($profitPercentage, 2),
                    ];
                }

                // Update order
                $order->total_amount = $totalAmount;
                $order->orderProducts()->createMany($orderProducts);
            }

            // Update other fields
            if ($request->has('customer_name')) {
                $order->customer_name = $request->customer_name;
            }
            if ($request->has('status')) {
                $order->status = $request->status;
            }

            $order->save();

            DB::commit();

            $order->load('orderProducts.product', 'orderProducts.stock');

            return response()->json([
                'success' => true,
                'message' => 'Order updated successfully',
                'data' => $order
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }


    public function destroy(string $id)
    {
        $order = Order::with('orderProducts')->findOrFail($id);

        DB::beginTransaction();
        try {
            // Restore stock quantities
            foreach ($order->orderProducts as $orderProduct) {
                $stock = Stock::findOrFail($orderProduct->stock_id);
                $previousQuantity = $stock->quantity;

                // Restore stock quantity
                $restoreQuantity = $orderProduct->quantity ?? 1;
                $stock->quantity += $restoreQuantity;
                $stock->last_update_at = now();
                $stock->save();

                // Create stock log
                StockLog::create([
                    'type' => 'Order-delete',
                    'stock_id' => $stock->id,
                    'product_id' => $stock->product_id,
                    'previous_quantity' => $previousQuantity,
                    'change_quantity' => $restoreQuantity,
                    'current_quantity' => $stock->quantity,
                ]);
            }

            // Delete order (cascade will delete order products)
            $order->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Place order (similar to store but with status Processing).
     */
    public function place(Request $request)
    {
        $request->validate([
            'customer_name' => 'required|string|max:255',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.stock_id' => 'required|exists:stocks,id',
            'products.*.quantity' => 'required|integer|min:1',
        ]);

        // Create order with Processing status
        $order = $this->store($request);

        if ($order->getStatusCode() === 201) {
            $orderData = json_decode($order->getContent(), true);
            $orderId = $orderData['data']['id'];

            $placedOrder = Order::find($orderId);
            $placedOrder->status = 'Processing';
            $placedOrder->save();

            $placedOrder->load('orderProducts.product', 'orderProducts.stock');

            return response()->json([
                'success' => true,
                'message' => 'Order placed successfully',
                'data' => $placedOrder
            ], 201);
        }

        return $order;
    }

    /**
     * Fake payment processing.
     */
    public function fakePayment(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
        ]);

        $order = Order::findOrFail($request->order_id);

        if ($order->status === 'Delivered') {
            return response()->json([
                'success' => false,
                'message' => 'Order is already delivered'
            ], 400);
        }

        // Simulate payment processing
        $order->status = 'Processing';
        $order->save();

        return response()->json([
            'success' => true,
            'message' => 'Payment processed successfully (fake)',
            'data' => $order
        ]);
    }

    /**
     * Generate unique invoice number.
     */
    private function generateInvoiceNumber(): string
    {
        do {
            $invoiceNumber = 'INV-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
        } while (Order::where('invoice_number', $invoiceNumber)->exists());

        return $invoiceNumber;
    }
}
