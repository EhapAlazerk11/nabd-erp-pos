<?php

namespace Modules\NabdBridge\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NabdCustomerController extends Controller
{
    /**
     * GET /api/nabd/customers
     *
     * Query params:
     *   - search (string) — matches name, email, or phone
     *   - group_id (int)
     *   - per_page (int, default 50, max 200)
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 50), 200);

        $query = Customer::with('group')->orderBy('name');

        if ($request->filled('search')) {
            $term = $request->string('search');
            $query->where(function ($q) use ($term): void {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%");
            });
        }

        if ($request->filled('group_id')) {
            $query->where('group_id', $request->integer('group_id'));
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
     * GET /api/nabd/customers/{id}
     */
    public function show(int $id): JsonResponse
    {
        $customer = Customer::with(['group', 'billing_address', 'shipping_address'])
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $customer,
        ]);
    }

    /**
     * POST /api/nabd/customers
     */
    public function store(Request $request): JsonResponse
    {
        $fields = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'unique:nexopos_customers,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'group_id' => ['nullable', 'integer', 'exists:nexopos_customers_groups,id'],
            'address' => ['nullable', 'array'],
            'address.billing' => ['nullable', 'array'],
            'address.shipping' => ['nullable', 'array'],
        ]);

        $customer = Customer::create([
            'name' => $fields['name'],
            'email' => $fields['email'] ?? null,
            'phone' => $fields['phone'] ?? null,
            'group_id' => $fields['group_id'] ?? CustomerGroup::first()?->id ?? 0,
            'author' => 0,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Customer created successfully.',
            'data' => $customer,
        ], 201);
    }

    /**
     * PUT /api/nabd/customers/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $customer = Customer::findOrFail($id);

        $fields = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['nullable', 'email', Rule::unique('nexopos_customers', 'email')->ignore($id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'group_id' => ['nullable', 'integer', 'exists:nexopos_customers_groups,id'],
        ]);

        $customer->update(array_filter($fields, fn ($v) => $v !== null));

        return response()->json([
            'status' => 'success',
            'message' => 'Customer updated successfully.',
            'data' => $customer->fresh(),
        ]);
    }

    /**
     * GET /api/nabd/customers/{id}/orders
     */
    public function orders(int $id): JsonResponse
    {
        $customer = Customer::findOrFail($id);

        $orders = $customer->orders()
            ->with('products')
            ->orderByDesc('created_at')
            ->paginate(50);

        return response()->json([
            'status' => 'success',
            'data' => $orders->items(),
            'meta' => [
                'total' => $orders->total(),
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
            ],
        ]);
    }
}
