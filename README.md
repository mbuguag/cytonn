# Task Management API

Laravel API built for the 2026 Laravel Engineer Intern take-home assignment For Cytonn .

## Features

- Create tasks
- List tasks
- Update task status
- Delete completed tasks
- Generate a daily task summary report

## Tech Stack

- PHP 8.3+
- Laravel 13
- MySQL

## Business Rules

- A task `title` cannot be duplicated on the same `due_date`
- `priority` must be one of `low`, `medium`, `high`
- `due_date` must be today or later
- Task status can only move forward: `pending -> in_progress -> done`
- Only tasks with status `done` can be deleted

## Local Setup

1. Clone the repository
2. Install dependencies:

```bash
composer install
```

3. Copy the environment file:

```bash
cp .env.example .env
```

4. Update database settings in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=task_api
DB_USERNAME=root
DB_PASSWORD=
```

5. Generate the app key:

```bash
php artisan key:generate
```

6. Run migrations:

```bash
php artisan migrate
```

7. Start the local server:

```bash
php artisan serve
```

The API will be available at:

```text
http://127.0.0.1:8000
```

## Running Tests

```bash
php artisan test
```

## Database Schema

### `tasks`

| Column | Type | Notes |
|---|---|---|
| `id` | integer | Primary key |
| `title` | string | Task title |
| `due_date` | date | Deadline |
| `priority` | enum | `low`, `medium`, `high` |
| `status` | enum | `pending`, `in_progress`, `done` |
| `created_at` | timestamp | Laravel default |
| `updated_at` | timestamp | Laravel default |

## API Endpoints

### Create Task

- Method: `POST`
- URL: `/api/tasks`

Example:

```bash
curl -X POST http://127.0.0.1:8000/api/tasks \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Finish coding challenge",
    "due_date": "2026-04-01",
    "priority": "high"
  }'
```

Example response:

```json
{
  "message": "Task created successfully.",
  "data": {
    "id": 1,
    "title": "Finish coding challenge",
    "due_date": "2026-04-01T00:00:00.000000Z",
    "priority": "high",
    "status": "pending",
    "created_at": "2026-03-29T10:00:00.000000Z",
    "updated_at": "2026-03-29T10:00:00.000000Z"
  }
}
```

### List Tasks

- Method: `GET`
- URL: `/api/tasks`
- Optional query: `status`

Examples:

```bash
curl http://127.0.0.1:8000/api/tasks
```

```bash
curl "http://127.0.0.1:8000/api/tasks?status=pending"
```

Sorting:

- Priority from `high` to `low`
- Then `due_date` ascending

Example response:

```json
{
  "message": "Tasks retrieved successfully.",
  "data": [
    {
      "id": 1,
      "title": "Finish coding challenge",
      "due_date": "2026-04-01T00:00:00.000000Z",
      "priority": "high",
      "status": "pending",
      "created_at": "2026-03-29T10:00:00.000000Z",
      "updated_at": "2026-03-29T10:00:00.000000Z"
    }
  ]
}
```

### Update Task Status

- Method: `PATCH`
- URL: `/api/tasks/{id}/status`

This endpoint auto-advances the task by one valid step only.

Example:

```bash
curl -X PATCH http://127.0.0.1:8000/api/tasks/1/status
```

Possible transitions:

- `pending -> in_progress`
- `in_progress -> done`

Example response:

```json
{
  "message": "Status updated to 'in_progress'.",
  "data": {
    "id": 1,
    "title": "Finish coding challenge",
    "due_date": "2026-04-01T00:00:00.000000Z",
    "priority": "high",
    "status": "in_progress",
    "created_at": "2026-03-29T10:00:00.000000Z",
    "updated_at": "2026-03-29T10:05:00.000000Z"
  }
}
```

### Delete Task

- Method: `DELETE`
- URL: `/api/tasks/{id}`

Only tasks with status `done` can be deleted.

Example:

```bash
curl -X DELETE http://127.0.0.1:8000/api/tasks/1
```

Example response:

```json
{
  "message": "Task deleted successfully.",
  "data": null
}
```

### Daily Report

- Method: `GET`
- URL: `/api/tasks/report?date=YYYY-MM-DD`

Example:

```bash
curl "http://127.0.0.1:8000/api/tasks/report?date=2026-03-29"
```

Example response:

```json
{
  "message": "Report generated successfully.",
  "data": {
    "date": "2026-03-29",
    "summary": {
      "high": {
        "pending": 2,
        "in_progress": 1,
        "done": 0
      },
      "medium": {
        "pending": 1,
        "in_progress": 0,
        "done": 3
      },
      "low": {
        "pending": 0,
        "in_progress": 0,
        "done": 1
      }
    }
  }
}
```

## Common Validation Errors

### Invalid task creation

Status code: `422 Unprocessable Entity`

Examples:

- Duplicate `title` on the same `due_date`
- Invalid `priority`
- `due_date` earlier than today

### Invalid status filter

Status code: `422 Unprocessable Entity`

Accepted values:

- `pending`
- `in_progress`
- `done`

### Deleting unfinished task

Status code: `403 Forbidden`

## Deployment Guide

This API can be deployed on Railway or Render with a MySQL database.

### Before Deployment

Make sure the project is committed and working locally with MySQL.

Recommended final checks:

```bash
php artisan config:clear
php artisan route:list
php artisan migrate:status
```

### Required Environment Variables

Set these values on your hosting platform:

```env
APP_NAME="Task API"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-app-url

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=your-db-host
DB_PORT=3306
DB_DATABASE=your-db-name
DB_USERNAME=your-db-user
DB_PASSWORD=your-db-password
```

Also generate an application key if your platform does not provide one:

```bash
php artisan key:generate --show
```

Copy the generated value into `APP_KEY`.

### Deploy on Railway

1. Push the project to GitHub
2. Create a new Railway project
3. Add a MySQL service
4. Add a web service from your GitHub repository
5. In the Railway variables tab, set:
   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - `APP_URL=https://your-railway-url`
   - `APP_KEY=base64:...`
   - MySQL variables from the Railway MySQL service
6. Set the start command to:

```bash
php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=$PORT
```

7. Deploy the service
8. Open the generated Railway URL and test:

```bash
curl https://your-railway-url/api/tasks
```

### Deploy on Render

1. Push the project to GitHub
2. Create a new MySQL database in Render
3. Create a new Web Service and connect your repository
4. Use these settings:
   - Build command: `composer install --no-dev --optimize-autoloader`
   - Start command: `php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=$PORT`
5. Set environment variables:
   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - `APP_URL=https://your-render-url`
   - `APP_KEY=base64:...`
   - Render MySQL connection values
6. Deploy the service
7. Verify the deployed API:

```bash
curl https://your-render-url/api/tasks
```

### Post-Deployment Checks

After deployment, verify:

- `GET /api/tasks` returns `200`
- `POST /api/tasks` creates a task successfully
- `PATCH /api/tasks/{id}/status` advances status correctly
- `DELETE /api/tasks/{id}` only deletes `done` tasks
- `GET /api/tasks/report?date=YYYY-MM-DD` returns the summary

### Recommended Submission Additions

Before submitting, add these to the README:

- The live base URL
- A note on which platform was used
- Any platform-specific setup choices
- Sample production request examples using the deployed URL

## Current Status

- MySQL environment template is configured
- Core task endpoints are implemented
- Online deployment URL can be added after deployment
