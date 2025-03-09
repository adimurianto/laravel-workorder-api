<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WorkOrder;

class DashboardController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/dashboard/stats",
     *     summary="Dashboard Statistics",
     *     tags={"Dashboard"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="assigned_operator_id",
     *         in="query",
     *         description="ID Operator",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Dashboard statistics retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="totalWorkOrders", type="integer", example=25),
     *             @OA\Property(property="pending", type="integer", example=5),
     *             @OA\Property(property="inProgress", type="integer", example=10),
     *             @OA\Property(property="completed", type="integer", example=8),
     *             @OA\Property(property="canceled", type="integer", example=2)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error"
     *     )
     * )
     */
    public function stats(Request $request)
    {
        $query = WorkOrder::query();
        
        if ($request->has('assigned_operator_id')) {
            $query->where('assigned_operator_id', $request->assigned_operator_id);
        }
        
        $stats = [
            'totalWorkOrders' => (clone $query)->count(),
            'pending' => (clone $query)->where('status', 'Pending')->count(),
            'inProgress' => (clone $query)->where('status', 'In Progress')->count(),
            'completed' => (clone $query)->where('status', 'Completed')->count(),
            'canceled' => (clone $query)->where('status', 'Canceled')->count(),
        ];
        
        return response()->json($stats);
    }
}
