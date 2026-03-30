<?php

namespace Tests\Feature;

use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_task(): void
    {
        $response = $this->postJson('/api/tasks', [
            'title' => 'Finish challenge',
            'due_date' => now()->addDay()->toDateString(),
            'priority' => 'high',
        ]);

        $response->assertCreated()
            ->assertJsonPath('message', 'Task created successfully.')
            ->assertJsonPath('data.title', 'Finish challenge')
            ->assertJsonPath('data.priority', 'high')
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('tasks', [
            'title' => 'Finish challenge',
            'priority' => 'high',
            'status' => 'pending',
        ]);
    }

    public function test_it_rejects_duplicate_title_for_same_due_date(): void
    {
        $dueDate = now()->addDay()->toDateString();

        Task::create([
            'title' => 'Duplicate title',
            'due_date' => $dueDate,
            'priority' => 'medium',
            'status' => 'pending',
        ]);

        $response = $this->postJson('/api/tasks', [
            'title' => 'Duplicate title',
            'due_date' => $dueDate,
            'priority' => 'high',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    public function test_it_rejects_past_due_date(): void
    {
        $response = $this->postJson('/api/tasks', [
            'title' => 'Old task',
            'due_date' => now()->subDay()->toDateString(),
            'priority' => 'low',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['due_date']);
    }

    public function test_it_rejects_invalid_priority(): void
    {
        $response = $this->postJson('/api/tasks', [
            'title' => 'Bad priority',
            'due_date' => now()->addDay()->toDateString(),
            'priority' => 'urgent',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['priority']);
    }

    public function test_it_lists_tasks_sorted_by_priority_then_due_date(): void
    {
        Task::create([
            'title' => 'Medium task',
            'due_date' => now()->addDays(3)->toDateString(),
            'priority' => 'medium',
            'status' => 'pending',
        ]);

        Task::create([
            'title' => 'High later',
            'due_date' => now()->addDays(2)->toDateString(),
            'priority' => 'high',
            'status' => 'pending',
        ]);

        Task::create([
            'title' => 'High sooner',
            'due_date' => now()->addDay()->toDateString(),
            'priority' => 'high',
            'status' => 'done',
        ]);

        Task::create([
            'title' => 'Low task',
            'due_date' => now()->addDay()->toDateString(),
            'priority' => 'low',
            'status' => 'pending',
        ]);

        $response = $this->getJson('/api/tasks');

        $response->assertOk()
            ->assertJsonPath('message', 'Tasks retrieved successfully.')
            ->assertJsonPath('data.0.title', 'High sooner')
            ->assertJsonPath('data.1.title', 'High later')
            ->assertJsonPath('data.2.title', 'Medium task')
            ->assertJsonPath('data.3.title', 'Low task');
    }

    public function test_it_filters_tasks_by_status(): void
    {
        Task::create([
            'title' => 'Pending task',
            'due_date' => now()->addDay()->toDateString(),
            'priority' => 'high',
            'status' => 'pending',
        ]);

        Task::create([
            'title' => 'Done task',
            'due_date' => now()->addDays(2)->toDateString(),
            'priority' => 'low',
            'status' => 'done',
        ]);

        $response = $this->getJson('/api/tasks?status=done');

        $response->assertOk()
            ->assertJsonPath('message', 'Tasks retrieved successfully.')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Done task')
            ->assertJsonPath('data.0.status', 'done');
    }

    public function test_it_rejects_invalid_status_filter(): void
    {
        $response = $this->getJson('/api/tasks?status=archived');

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Invalid status. Use: pending, in_progress, done.');
    }

    public function test_it_returns_meaningful_json_when_no_tasks_exist(): void
    {
        $response = $this->getJson('/api/tasks');

        $response->assertOk()
            ->assertExactJson([
                'message' => 'No tasks found.',
                'data' => [],
            ]);
    }

    public function test_it_advances_task_status_one_step_at_a_time(): void
    {
        $task = Task::create([
            'title' => 'Status task',
            'due_date' => now()->addDay()->toDateString(),
            'priority' => 'medium',
            'status' => 'pending',
        ]);

        $firstResponse = $this->patchJson("/api/tasks/{$task->id}/status");
        $firstResponse->assertOk()
            ->assertJsonPath('data.status', 'in_progress');

        $secondResponse = $this->patchJson("/api/tasks/{$task->id}/status");
        $secondResponse->assertOk()
            ->assertJsonPath('data.status', 'done');
    }

    public function test_it_rejects_status_update_for_completed_task(): void
    {
        $task = Task::create([
            'title' => 'Completed task',
            'due_date' => now()->addDay()->toDateString(),
            'priority' => 'high',
            'status' => 'done',
        ]);

        $response = $this->patchJson("/api/tasks/{$task->id}/status");

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Task is already done. No further transitions allowed.');
    }

    public function test_it_allows_deleting_done_tasks(): void
    {
        $task = Task::create([
            'title' => 'Delete me',
            'due_date' => now()->addDay()->toDateString(),
            'priority' => 'low',
            'status' => 'done',
        ]);

        $response = $this->deleteJson("/api/tasks/{$task->id}");

        $response->assertOk()
            ->assertJsonPath('message', 'Task deleted successfully.');

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_it_blocks_deleting_non_done_tasks(): void
    {
        $task = Task::create([
            'title' => 'Cannot delete yet',
            'due_date' => now()->addDay()->toDateString(),
            'priority' => 'low',
            'status' => 'pending',
        ]);

        $response = $this->deleteJson("/api/tasks/{$task->id}");

        $response->assertForbidden()
            ->assertJsonPath('message', 'Forbidden. Only done tasks can be deleted.');
    }

    public function test_it_returns_daily_report_counts(): void
    {
        $date = now()->addDay()->toDateString();

        Task::create([
            'title' => 'High pending',
            'due_date' => $date,
            'priority' => 'high',
            'status' => 'pending',
        ]);

        Task::create([
            'title' => 'High in progress',
            'due_date' => $date,
            'priority' => 'high',
            'status' => 'in_progress',
        ]);

        Task::create([
            'title' => 'Medium done',
            'due_date' => $date,
            'priority' => 'medium',
            'status' => 'done',
        ]);

        Task::create([
            'title' => 'Low done',
            'due_date' => $date,
            'priority' => 'low',
            'status' => 'done',
        ]);

        Task::create([
            'title' => 'Different day',
            'due_date' => now()->addDays(2)->toDateString(),
            'priority' => 'high',
            'status' => 'done',
        ]);

        $response = $this->getJson("/api/tasks/report?date={$date}");

        $response->assertOk()
            ->assertJsonPath('message', 'Report generated successfully.')
            ->assertJsonPath('data.date', $date)
            ->assertJsonPath('data.summary.high.pending', 1)
            ->assertJsonPath('data.summary.high.in_progress', 1)
            ->assertJsonPath('data.summary.high.done', 0)
            ->assertJsonPath('data.summary.medium.pending', 0)
            ->assertJsonPath('data.summary.medium.in_progress', 0)
            ->assertJsonPath('data.summary.medium.done', 1)
            ->assertJsonPath('data.summary.low.pending', 0)
            ->assertJsonPath('data.summary.low.in_progress', 0)
            ->assertJsonPath('data.summary.low.done', 1);
    }
}
