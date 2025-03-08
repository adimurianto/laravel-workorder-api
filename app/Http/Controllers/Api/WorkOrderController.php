<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkOrder;
use App\Models\WorkOrderProgress;
use App\Models\WorkOrderTimeTracking;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class WorkOrderController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/work-orders",
     *     summary="Get a list of workorders",
     *     tags={"Work Orders"},
     *     security={{
     *         "bearerAuth": {}
     *     }},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="product_name",
     *         in="query",
     *         description="Filter by product name",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="assigned_operator_id",
     *         in="query",
     *         description="Filter by operator ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         description="Sort by column",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="sort_order",
     *         in="query",
     *         description="Sort order (asc/desc)",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number (default: 1)",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page (default: 10)",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="A list of workorders",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="work_order_number", type="string"),
     *                 @OA\Property(property="product_name", type="string"),
     *                 @OA\Property(property="quantity", type="integer"),
     *                 @OA\Property(property="deadline", type="string", format="date"),
     *                 @OA\Property(property="status", type="string"),
     *                 @OA\Property(property="assigned_operator_id", type="integer"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = WorkOrder::query();

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('product_name')) {
            $query->where('product_name', 'like', '%' . $request->product_name . '%');
        }
        if ($request->has('assigned_operator_id')) {
            $query->where('assigned_operator_id', $request->assigned_operator_id);
        }

        // Apply sorting
        $sortColumn = $request->input('sort_by', 'created_at'); // Default sort by created_at
        $sortOrder = $request->input('sort_order', 'desc'); // Default sort order is descending
        
        // Validate sort order
        $sortOrder = in_array(strtolower($sortOrder), ['asc', 'desc']) ? strtolower($sortOrder) : 'desc';
        
        // Add sorting to query
        $query->orderBy($sortColumn, $sortOrder);

        // Apply pagination with validation
        $page = max(1, (int)$request->input('page', 1)); // Ensure page is at least 1
        $perPage = max(1, min(100, (int)$request->input('per_page', 10))); // Limit per_page between 1 and 100
        
        $workOrders = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json($workOrders);
    }

    private function generateWorkOrderNumber()
    {
        $date = now()->format('Ymd');
        $lastWorkOrder = WorkOrder::where('work_order_number', 'like', "WO-{$date}-%")
            ->orderBy('work_order_number', 'desc')
            ->first();

        if (!$lastWorkOrder) {
            return "WO-{$date}-001";
        }

        $lastNumber = intval(substr($lastWorkOrder->work_order_number, -3));
        $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        return "WO-{$date}-{$newNumber}";
    }

    /**
     * @OA\Post(
     *     path="/api/work-orders",
     *     summary="Create a new work order",
     *     tags={"Work Orders"},
     *     security={{
     *         "bearerAuth": {}
     *     }},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"product_name", "quantity", "deadline", "status", "assigned_operator_id"},
     *             @OA\Property(property="product_name", type="string", example="Product XYZ"),
     *             @OA\Property(property="quantity", type="integer", example=10),
     *             @OA\Property(property="deadline", type="string", format="date", example="2024-01-31"),
     *             @OA\Property(property="status", type="string", example="pending"),
     *             @OA\Property(property="assigned_operator_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Work order created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="work_order_number", type="string"),
     *             @OA\Property(property="product_name", type="string"),
     *             @OA\Property(property="quantity", type="integer"),
     *             @OA\Property(property="deadline", type="string", format="date"),
     *             @OA\Property(property="status", type="string"),
     *             @OA\Property(property="assigned_operator_id", type="integer"),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_name' => 'required|string',
            'quantity' => 'required|integer|min:1',
            'deadline' => 'required|date',
            'status' => 'required|string',
            'assigned_operator_id' => 'required|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->all();
        $data['work_order_number'] = $this->generateWorkOrderNumber();

        $workOrder = WorkOrder::create($data);
        return response()->json($workOrder, 201);
    }

    /**
     * @OA\Get(
     *     path="/api/work-orders/{id}",
     *     summary="Get a specific work order",
     *     tags={"Work Orders"},
     *     security={{
     *         "bearerAuth": {}
     *     }},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Work order ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Work order details",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="work_order_number", type="string"),
     *             @OA\Property(property="product_name", type="string"),
     *             @OA\Property(property="quantity", type="integer"),
     *             @OA\Property(property="deadline", type="string", format="date"),
     *             @OA\Property(property="status", type="string"),
     *             @OA\Property(property="assigned_operator_id", type="integer"),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Work order not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Work order not found")
     *         )
     *     )
     * )
     */
    public function show($id)
    {
        $workOrder = WorkOrder::find($id);
        
        if (!$workOrder) {
            return response()->json(['message' => 'Work order not found'], 404);
        }

        return response()->json($workOrder);
    }

    /**
     * @OA\Put(
     *     path="/api/work-orders/{id}",
     *     summary="Update a work order",
     *     tags={"Work Orders"},
     *     security={{
     *         "bearerAuth": {}
     *     }},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Work order ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
    *         required=true,
    *         @OA\JsonContent(
    *             required={"status", "assigned_operator_id"},
    *             @OA\Property(property="status", type="string", enum={"Pending", "In Progress", "Completed", "Cancelled"}, description="Status of the work order"),
    *             @OA\Property(property="stage_note", type="string", description="Note for the work order update"),
    *             @OA\Property(property="quantity_done", type="integer", description="Quantity of the product")
    *         )
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Work order updated successfully",
    *         @OA\JsonContent(
    *             @OA\Property(property="message", type="string", example="Work order updated by Production Manager."),
    *             @OA\Property(property="work_order", type="object", example="{}")
    *         )
    *     ),
    *     @OA\Response(
    *         response=400,
    *         description="Invalid status transition or input data",
    *         @OA\JsonContent(
    *             @OA\Property(property="message", type="string", example="Operator can only update status from Pending to In Progress or In Progress to Completed.")
    *         )
    *     ),
    *     @OA\Response(
    *         response=403,
    *         description="Unauthorized action",
    *         @OA\JsonContent(
    *             @OA\Property(property="message", type="string", example="Unauthorized action.")
    *         )
    *     ),
    *     @OA\Response(
    *         response=404,
    *         description="Work order not found",
    *         @OA\JsonContent(
    *             @OA\Property(property="message", type="string", example="Work order not found")
    *         )
    *     )
    * )
    */
    public function update(Request $request, $id)
    {
        $workOrder = WorkOrder::find($id);

        if (!$workOrder) {
            return response()->json(['message' => 'Work order not found'], 404);
        }

        $authUser = Auth::user();
        $user = User::find($authUser->id);

        $validator = Validator::make($request->all(), [
            'status' => 'string|in:Pending,In Progress,Completed,Cancelled',
            'assigned_operator_id' => 'exists:users,id',
            'stage_note' => 'string',
            'quantity_done' => 'integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($user->role->name === 'Production Manager') {
            $workOrder->update([
                'status' => $request->status,
                'assigned_operator_id' => $request->assigned_operator_id ?? $workOrder->assigned_operator_id,
            ]);

            if ($request->has('status') && $workOrder->status !== $request->status) {
                $progress = new WorkOrderProgress([
                    'work_order_id' => $workOrder->id,
                    'status' => $request->status,
                    'stage_note' => $request->stage_note ?? null,
                    'quantity_done' => $request->quantity_done ?? null,
                    'operator_id' => $authUser->id,
                ]);
                $progress->save();
            }

            return response()->json(['message' => 'Work order updated by Production Manager.', 'work_order' => $workOrder]);
        }

        if ($user->role->name === 'Operator') {
            if (($workOrder->status === 'Pending' || $workOrder->status === 'In Progress') && $request->status === 'In Progress') {
                $workOrder->update([
                    'status' => $request->status,
                ]);

                $progress = new WorkOrderProgress([
                    'work_order_id' => $workOrder->id,
                    'status' => $request->status,
                    'stage_note' => $request->stage_note ?? null,
                    'quantity_done' => $request->quantity_done ?? 0,
                    'operator_id' => $authUser->id,
                ]);
                $progress->save();

                return response()->json(['message' => 'Work order status updated to '.$request->status.'.', 'work_order' => $workOrder]);
            } elseif ($workOrder->status === 'In Progress' && $request->status === 'Completed') {
                $workOrder->update([
                    'status' => $request->status,
                ]);

                $progress = new WorkOrderProgress([
                    'work_order_id' => $workOrder->id,
                    'status' => $request->status,
                    'stage_note' => $request->stage_note ?? null,
                    'quantity_done' => $request->quantity_done ?? 0,
                    'operator_id' => $authUser->id,
                ]);
                $progress->save();

                return response()->json(['message' => 'Work order status updated to '.$request->status.'.', 'work_order' => $workOrder]);
            } else {
                return response()->json(['message' => 'Operator can only update status from Pending to In Progress or In Progress to Completed.'], 400);
            }
        }

        return response()->json(['message' => 'Unauthorized action.'], 403);
    }

    /**
     * @OA\Delete(
     *     path="/api/work-orders/{id}",
     *     summary="Delete a work order",
     *     tags={"Work Orders"},
     *     security={{
     *         "bearerAuth": {}
     *     }},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Work order ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Work order deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Work order deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Work order not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Work order not found")
     *         )
     *     )
     * )
     */
    public function destroy($id)
    {
        $workOrder = WorkOrder::find($id);
        
        if (!$workOrder) {
            return response()->json(['message' => 'Work order not found'], 404);
        }

        $workOrder->delete();
        return response()->json(['message' => 'Work order deleted successfully']);
    }
}