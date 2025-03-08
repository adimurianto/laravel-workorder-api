<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkOrderProgress extends Model
{
    protected $fillable = ['work_order_id', 'status', 'stage_note', 'quantity_done', 'operator_id'];

    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
