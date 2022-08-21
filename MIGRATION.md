# Migration to Neos 9.0 (Event-Sourced)

# 1. Composer Dependency Updates

composer require rector/rector --dev
// TODO: RENAME TO neos/rector-rules ??
composer require neos/contentrepository-rector --dev

cp Packages/Neos/Neos.ContentRepository.Rector/rector.template.php rector.php
```php
```

# 2. Code Migrations

# 3. Data Migration
