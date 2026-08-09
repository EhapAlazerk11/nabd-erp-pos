<?php

namespace Modules\NabdBridge\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\DashboardDay;
use App\Models\Order;
use App\Models\Product;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NabdReportController extends Controller
{
    public function __construct(protected ReportService $reportService) {}

    /**
     * GET /api/nabd/reports/summary
     *
     * Quick stats for today or a given date.
     * Query params:
     *   - date (Y-m-d, default today)
     */
    public function summary(Request $request): JsonResponse
    {
        $date = $request->filled('date')
            ? Carbon::parse($request->string('date'))->format('Y-m-d')
            : today()->toDateString();

        $startOfDay = $date . ' 00:00:00';
        $endOfDay = $date . ' 23:59:59';

        $orders = Order::whereBetween('created_at', [$startOfDay, $endOfDay]);

        $totalOrders = (clone $orders)->count();
        $totalRevenue = (clone $orders)->where('payment_status', Order::PAYMENT_PAID)->sum('total');
        $totalVoided = (clone $orders)->where('payment_status', Order::PAYMENT_VOID)->count();
        $totalRefunded = (clone $orders)->whereIn('payment_status', [
            Order::PAYMENT_REFUNDED,
            Order::PAYMENT_PARTIALLY_REFUNDED,
        ])->count();

        // Try to pull from the pre-computed dashboard day if available
        $dashboardDay = DashboardDay::where('day_of_year', Carbon::parse($date)->dayOfYear)
            ->where('year', Carbon::parse($date)->year)
            ->first();

        return response()->json([
            'status' => 'success',
            'data' => [
                'date' => $date,
                'total_orders' => $totalOrders,
                'total_revenue' => round((float) $totalRevenue, 2),
                'total_voided' => $totalVoided,
                'total_refunded' => $totalRefunded,
                'dashboard_day' => $dashboardDay,
            ],
        ]);
    }

    /**
     * GET /api/nabd/reports/sales
     *
     * Sales report for a date range.
     * Query params:
     *   - from (Y-m-d, required)
     *   - to (Y-m-d, required)
     *   - type (string, optional)
     */
    public function sales(Request $request): JsonResponse
    {
        $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
        ]);

        $report = $this->reportService->getSaleReport(
            startDate: $request->string('from'),
            endDate: $request->string('to'),
            type: $request->string('type'),
            user_id: null,
            categories_id: null,
        );

        return response()->json([
            'status' => 'success',
            'data' => $report,
        ]);
    }

    /**
     * GET /api/nabd/reports/low-stock
     *
     * Products whose stock is at or below the low_quantity threshold.
     */
    public function lowStock(): JsonResponse
    {
        $products = $this->reportService->getLowStockProducts(
            categories: null,
            units: null,
        );

        return response()->json([
            'status' => 'success',
            'data' => $products,
        ]);
    }

    /**
     * GET /api/nabd/reports/stock
     *
     * Full inventory stock report.
     * Query params:
     *   - category_id (int, optional)
     */
    public function stock(Request $request): JsonResponse
    {
        $report = $this->reportService->getStockReport(
            categories: $request->filled('category_id') ? [$request->integer('category_id')] : null,
            units: null,
        );

        return response()->json([
            'status' => 'success',
            'data' => $report,
        ]);
    }

    /**
     * GET /api/nabd/reports/products
     *
     * Best/worst selling products in a date range.
     * Query params:
     *   - from (Y-m-d, required)
     *   - to (Y-m-d, required)
     *   - sort (asc|desc, default desc)
     */
    public function products(Request $request): JsonResponse
    {
        $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
            'sort' => ['nullable', 'in:asc,desc'],
        ]);

        $report = $this->reportService->getProductSalesDiff(
            startDate: $request->string('from'),
            endDate: $request->string('to'),
            sort: $request->string('sort', 'desc'),
        );

        return response()->json([
            'status' => 'success',
            'data' => $report,
        ]);
    }
}
