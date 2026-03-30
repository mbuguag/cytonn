<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    /**
     * POST /api/tasks
     * Create a new task.
     * Rules:
     *   - title must be unique per due_date
     *   - priority must be low, medium, or high
     *   - due_date must be today or in the future
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title'    => [
                'required',
                'string',
                'max:255',
                // Unique combination of title + due_date
                Rule::unique('tasks')->where(function ($query) use ($request) {
                    return $query->where('due_date', $request->due_date);
                }),
            ],
            'due_date' => ['required', 'date', 'date_format:Y-m-d', 'after_or_equal:today'],
            'priority' => ['required', Rule::in(['low', 'medium', 'high'])],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $task = Task::create([
            'title'    => $request->title,
            'due_date' => $request->due_date,
            'priority' => $request->priority,
            'status'   => 'pending', // always starts as pending
        ]);

        return response()->json($task, 201);
    }

    /**
     * GET /api/tasks
     * List all tasks.
     * Rules:
     *   - Sorted by priority descending (high → low), then due_date ascending
     *   - Optional ?status= filter (pending, in_progress, done)
     */
    public function index(Request $request): JsonResponse
    {
        $query = Task::orderByRaw(Task::PRIORITY_ORDER . ' DESC')
            ->orderBy('due_date', 'asc');

        // Optional status filter
        if ($request->has('status')) {
            $status = $request->query('status');

            // Validate the status value before filtering
            if (!in_array($status, ['pending', 'in_progress', 'done'])) {
                return response()->json([
                    'message' => 'Invalid status value. Use: pending, in_progress, done.',
                ], 422);
            }

            $query->where('status', $status);
        }

        $tasks = $query->get();

        if ($tasks->isEmpty()) {
            return response()->json([
                'message' => 'No tasks found.',
                'data'    => [],
            ], 200);
        }

        return response()->json($tasks, 200);
    }

    /**
     * PATCH /api/tasks/{id}/status
     * Advance a task's status by exactly one step.
     * Rules:
     *   - pending → in_progress → done only
     *   - Cannot skip or revert
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $task = Task::find($id);

        if (!$task) {
            return response()->json(['message' => 'Task not found.'], 404);
        }

        $nextStatus = $task->nextStatus();

        // Task is already at 'done' — no further transitions allowed
        if ($nextStatus === null) {
            return response()->json([
                'message' => 'Task is already done. No further status transitions allowed.',
            ], 422);
        }

        $task->status = $nextStatus;
        $task->save();

        return response()->json([
            'message' => "Status updated to '{$nextStatus}'.",
            'data'    => $task,
        ], 200);
    }

    /**
     * DELETE /api/tasks/{id}
     * Delete a task.
     * Rules:
     *   - Only tasks with status 'done' can be deleted
     *   - Returns 403 Forbidden otherwise
     */
    public function destroy(int $id): JsonResponse
    {
        $task = Task::find($id);

        if (!$task) {
            return response()->json(['message' => 'Task not found.'], 404);
        }

        if ($task->status !== 'done') {
            return response()->json([
                'message' => 'Forbidden. Only completed (done) tasks can be deleted.',
            ], 403);
        }

        $task->delete();

        return response()->json(['message' => 'Task deleted successfully.'], 200);
    }

    /**
     * GET /api/tasks/report?date=YYYY-MM-DD
     * Bonus: Daily report — counts per priority and status for a given date.
     */
    public function report(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date' => ['required', 'date', 'date_format:Y-m-d'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Please provide a valid date in YYYY-MM-DD format.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $date  = $request->query('date');
        $tasks = Task::whereDate('due_date', $date)->get();

        $priorities = ['high', 'medium', 'low'];
        $statuses   = ['pending', 'in_progress', 'done'];

        // Build the summary structure — default all counts to 0
        $summary = [];
        foreach ($priorities as $priority) {
            foreach ($statuses as $status) {
                $summary[$priority][$status] = 0;
            }
        }

        // Count tasks into the summary
        foreach ($tasks as $task) {
            $summary[$task->priority][$task->status]++;
        }

        return response()->json([
            'date'    => $date,
            'summary' => $summary,
        ], 200);
    }
}
