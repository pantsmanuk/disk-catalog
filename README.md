# Disk Catalogue

Laravel application for maintaining a physical disk inventory and exporting it
as Markdown.

## Setup

```bash
composer run setup
php artisan db:seed
composer run dev
```

Seeding uses `storage/app/private/storage-audit.md` when present. Otherwise it
loads synthetic records from `resources/storage-audit.example.md`.

## Private data

- Keep real inventory only in `storage/app/private/storage-audit.md` and the
  local SQLite database. Both locations are ignored by Git.
- Treat exported Markdown files as sensitive operational data.
- Never force-add ignored environment, database, storage, log, or build files.
- This application has no authentication. Run it only on a trusted local or
  private network unless access control is added.
