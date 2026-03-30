<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $fillable = [
        'title',
        'due_date',
        'priority',
        'status',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    /**
     * Raw SQL expression for priority sorting.
     * high=3, medium=2, low=1
     */
    public const PRIORITY_ORDER = "CASE priority
        WHEN 'high'   THEN 3
        WHEN 'medium' THEN 2
        WHEN 'low'    THEN 1
        ELSE 0
    END";

    
    public const STATUS_TRANSITIONS = [
        'pending'     => 'in_progress',
        'in_progress' => 'done',
    ];

    /**
     * Returns the next valid status, or null if already at 'done'.
     */
    public function nextStatus(): ?string
    {
        return self::STATUS_TRANSITIONS[$this->status] ?? null;
    }
}
