<?php

namespace Modules\NabdBridge\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrdersService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NabdOrderController extends Controller
{
    public function __construct(protected OrdersService $ordersService) {}

    /**
     * GET /api/nabd/orders
     *
     * Query params:
     *   - from (date Y-m-d)
     *   - to (date Y-m-d)
     *   - payment_status (paid|unpaid|partially_paid|refunded|hold|order_void)
     *   - per_page (int, default 50, max 200)
     *   - page (int)
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 50), 200);

        $query = Order::with(['customer', 'products', 'payments'])
            ->orderByDesc('created_at');

        if ($request->filled('from')) {
            $query->where('created_at', '>=', $request->string('from') . ' 00:00:00');
        }

        if ($request->filled('to')) {
            $query->where('created_at', '<=', $request->string('to') . ' 23:59:59');
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->string('payment_status'));
        }

        $paginator = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $paginator->items(),
            'meta' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    /**
     * GET /api/nabd/orders/{id}
     */
    public function show(int $id): JsonResponse
    {
        $order = Order::with([
            'customer',
            'products.unit',
            'payments',
            'taxes',
            'refunds',
            'billing_address',
            'shipping_address',
        ])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $order,
        ]);
    }

    /**
     * POST /api/nabd/orders
     *
     * Creates a new order via the NexoPOS OrdersService.
     * Expects the same payload as the standard NexoPOS POST /api/orders.
     */
    public function store(Request $request): JsonResponse
    {
        $fields = $request->validate([
            'products' => ['required', 'array', 'min:1'],
            'products.*.product_id' => ['required', 'integer'],
            'products.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'products.*.unit_price' => ['required', 'numeric', 'min:0'],
            'customer_id' => ['nullable', 'integer'],
            'payment_status' => ['nullable', 'string'],
            'payments' => ['nullable', 'array'],
            'type' => ['nullable', 'string', 'in:takeaway,delivery'],
            'note' => ['nullable', 'string'],
            'shipping' => ['nullable', 'numeric', 'min:0'],
            'discount_type' => ['nullable', 'string', 'in:flat,percentage'],
            'discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'discount' => ['nullable', 'numeric', 'min:0'],
        ]);

        try {
            $order = $this->ordersService->create($fields);

            return response()->json([
                'status' => 'success',
                'message' => 'Order created successfully.',
                'data' => Order::with(['products', 'payments'])->find($order->id),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * PATCH /api/nabd/orders/{id}/status
     *
     * Updates delivery or processing status of an order.
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $order = Order::findOrFail($id);

        $request->validate([
            'delivery_status' => ['nullable', 'string', 'in:pending,ongoing,delivered,error'],
            'process_status' => ['nullable', 'string', 'in:pending,ongoing,ready,error'],
        ]);

        if ($request->filled('delivery_status')) {
            $order->delivery_status = $request->string('delivery_status');
        }

        if ($request->filled('process_status')) {
            $order->process_status = $request->string('process_status');
        }

        $order->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Order status updated.',
            'data' => $order->only(['id', 'delivery_status', 'process_status', 'payment_status', 'updated_at']),
        ]);
    }
}
