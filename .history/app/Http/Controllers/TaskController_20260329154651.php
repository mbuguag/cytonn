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
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => [
                'required',
                'string',
                'max:255',
                Rule::unique('tasks')->where(fn($q) => $q->where('due_date', $request->due_date)),
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
            'status'   => 'pending',
        ]);

        return response()->json($task, 201);
    }

    /**
     * GET /api/tasks
     */
    public function index(Request $request): JsonResponse
    {
        $query = Task::orderByRaw(Task::PRIORITY_ORDER . ' DESC')
            ->orderBy('due_date', 'asc');

        if ($request->has('status')) {
            $status = $request->query('status');
            if (!in_array($status, ['pending', 'in_progress', 'done'])) {
                return response()->json([
                    'message' => 'Invalid status. Use: pending, in_progress, done.',
                ], 422);
            }
            $query->where('status', $status);
        }

        $tasks = $query->get();

        if ($tasks->isEmpty()) {
            return response()->json(['message' => 'No tasks found.', 'data' => []], 200);
        }

        return response()->json($tasks, 200);
    }

    /**
     * PATCH /api/tasks/{id}/status
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $task = Task::find($id);

        if (!$task) {
            return response()->json(['message' => 'Task not found.'], 404);
        }

        $nextStatus = $task->nextStatus();

        if ($nextStatus === null) {
            return response()->json([
                'message' => 'Task is already done. No further transitions allowed.',
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
     */
    public function destroy(int $id): JsonResponse
    {
        $task = Task::find($id);

        if (!$task) {
            return response()->json(['message' => 'Task not found.'], 404);
        }

        if ($task->status !== 'done') {
            return response()->json([
                'message' => 'Forbidden. Only done tasks can be deleted.',
            ], 403);
        }

        $task->delete();

        return response()->json(['message' => 'Task deleted successfully.'], 200);
    }

    /**
     * GET /api/tasks/report?date=YYYY-MM-DD  (Bonus)
     */
    public function report(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date' => ['required', 'date', 'date_format:Y-m-d'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Provide a valid date: YYYY-MM-DD.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $date  = $request->query('date');
        $tasks = Task::whereDate('due_date', $date)->get();

        $summary = [];
        foreach (['high', 'medium', 'low'] as $priority) {
            foreach (['pending', 'in_progress', 'done'] as $status) {
                $summary[$priority][$status] = 0;
            }
        }

        foreach ($tasks as $task) {
            $summary[$task->priority][$task->status]++;
        }

        return response()->json(['date' => $date, 'summary' => $summary], 200);
    }
}
