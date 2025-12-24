# Setup Guide

This guide covers the AI Chat and Excel import monitoring features.

## 1. Dependencies

Composer:
- `composer require openai-php/laravel`
- `composer require maatwebsite/excel`

NPM:
- `npm install highlight.js`

Note: OpenAI and Excel packages are already present in `composer.json`.

## 2. Environment Variables

Add or confirm these values in `.env`:
- `OPENAI_API_KEY=your-key`
- `OPENAI_MODEL=gpt-4-turbo-preview`
- `QUEUE_CONNECTION=database`
- `FILESYSTEM_DISK=local`

Optional:
- `OPENAI_ORGANIZATION=your-org`
- `OPENAI_PROJECT=your-project`

## 3. Migrations

Run migrations (includes chat tables and import monitoring tables):

```
php artisan migrate
```

Queue tables (only if missing):
```
php artisan queue:table
php artisan queue:batches-table
php artisan migrate
```

## 4. Queue Worker

Run a worker for batch imports:

```
php artisan queue:work --queue=imports,default
```

For production, use Supervisor or systemd to keep the worker alive.

## 5. Build Assets

Install JS dependencies and build:

```
npm install
npm run build
```

## 6. Usage

AI Chat:
- Open Filament and navigate to **AI Chat**.
- Start a new chat and send messages.
- Ctrl+Enter to send.

User Import Monitoring:
- Go to **Users** list.
- Click **Import Users (Batch)** and upload your Excel file.
- You will be redirected to the monitoring page.

## 7. Troubleshooting

OpenAI errors:
- Verify `OPENAI_API_KEY`.
- Check network access and API quota.

Queue not processing:
- Ensure a queue worker is running.
- Confirm `QUEUE_CONNECTION=database`.

Excel import errors:
- Confirm column mapping: A=gpid, B=name, D=role, E=department.
- Check file format (.xlsx recommended).

Highlight.js not applying:
- Ensure `npm install` and `npm run build` completed.
- Refresh the browser cache.
