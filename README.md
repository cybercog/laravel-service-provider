# Laravel Service Provider

Basic service provider mockup with utility functions to speedup packages deployment.

## Installation

First, add dependency in `composer.json`:

```js
"require": {
    "cybercog/laravel-service-provider": "*",
},
```

Perform `composer update`.

## Usage

Create service provider in your package and extend this one.

```php
<?php

namespace Vendor\Package;

use Cog\ServiceProvider\BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    public function boot()
    {
        $this->setup(__DIR__)
             ->publishMigrations()
             ->publishConfig()
             ->publishViews()
             ->publishAssets()
             ->loadViews()
             ->loadTranslations()
             ->mergeConfig('package');
    }
}
```

### Migration stubs

To create migrations you could add usual Laravel's migration file and place them in package's `database/migrations` directory.

Migration files has specific naming convention `0001_create_my_table.stub`:

- First 4 digits are required to save chronological order of migration files. *This is a fix for a cases when your migration try to use other package's tables which are positioning below your migrations, because of static timestamp in name and ordering not by publish date, but by date of migration's development.*
- Name of file is class name converted to `snake_case` (as usual for migrations in Laravel).
- Extenstion `.php` replaced with `.stub` to prevent class names conflicts in package and application's migrations directory.

When you are publishing migrations:

- Prefixed digits are converting to current timestamp.
- Extension is swapping to `.php`.
- Prepared migration file moving to application's migrations directory.