<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        Task::factory()->createMany([
            [
                'title' => 'Prepare project README',
                'due_date' => now()->toDateString(),
                'priority' => 'high',
                'status' => 'pending',
            ],
            [
                'title' => 'Implement API tests',
                'due_date' => now()->addDay()->toDateString(),
                'priority' => 'high',
                'status' => 'in_progress',
            ],
            [
                'title' => 'Review MySQL configuration',
                'due_date' => now()->addDays(2)->toDateString(),
                'priority' => 'medium',
                'status' => 'done',
            ],
            [
                'title' => 'Deploy application online',
                'due_date' => now()->addDays(3)->toDateString(),
                'priority' => 'medium',
                'status' => 'pending',
            ],
            [
                'title' => 'Clean up final submission',
                'due_date' => now()->addDays(4)->toDateString(),
                'priority' => 'low',
                'status' => 'pending',
            ],
        ]);
    }
}
