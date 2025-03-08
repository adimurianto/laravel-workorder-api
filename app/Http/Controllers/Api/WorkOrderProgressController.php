<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\WorkOrderProgress;

class WorkOrderProgressController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/work-order-progress/{work_order_id}",
     *     summary="Get work order progress by ID",
     *     tags={"Work Order Progress"},
     *     security={{
     *         "bearerAuth": {}
     *     }},
     *     @OA\Parameter(
     *         name="work_order_id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="work_order_id", type="integer"),
     *                 @OA\Property(property="status", type="string"),
     *                 @OA\Property(property="stage_note", type="string"),
     *                 @OA\Property(property="quantity_done", type="integer"),
     *                 @OA\Property(property="operator_id", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="WorkOrderProgress not found"
     *     )
     * )
     */
    public function index($id)
    {
        $workOrderProgress = WorkOrderProgress::where('work_order_id', $id)->get();
        if (!$workOrderProgress) {
            return response()->json(['message' => 'WorkOrderProgress not found'], 404);
        }
        return response()->json($workOrderProgress);
    }
}
