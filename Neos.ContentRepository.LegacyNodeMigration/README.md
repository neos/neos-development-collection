# Content Repository Migration

This repository is a **read-only subsplit** of a package that is part of the [Neos Development Collection](https://github.com/neos/neos-development-collection)

-----

#### Migrating an existing (Neos < 9.0) Site

``` bash
# the following config points to a Neos 8.0 database (adjust to your needs)
./flow site:exportLegacyData --path ./migratedContent --config '{"dbal": {"dbname": "neos80"}, "resourcesPath": "/path/to/neos-8.0/Data/Persistent/Resources"}'
# import the migrated data
./flow site:importAll --path ./migratedContent
```
