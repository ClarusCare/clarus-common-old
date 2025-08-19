# clarus-shared-models
Common Models and Database Entities for Laravel projects.

## Installation

### Option 1: Via Composer (Recommended)

1. Add this repository to your Laravel project's `composer.json`:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/your-username/clarus-shared-models.git"
        }
    ],
    "require": {
        "clarus/shared-models": "dev-main"
    }
}
```

2. Run `composer install`

3. In your Laravel project's User model, extend the shared model:

```php
<?php

namespace App\Models;

use ClarusSharedModels\Models\User as SharedUser;

// Add any project-specific traits
use App\Traits\HasRoles; // Only if this trait exists in your project
use Illuminate\Database\Eloquent\SoftDeletes; // Only if needed

class User extends SharedUser
{
    // Add project-specific traits
    use HasRoles, SoftDeletes; // Only add traits that exist in your project
    
    // Add any project-specific methods or overrides here
}
```

### Option 2: Git Submodule

1. Add as submodule:
```bash
git submodule add https://github.com/your-username/clarus-shared-models.git shared-models
```

2. Add to composer.json autoload:
```json
{
    "autoload": {
        "psr-4": {
            "ClarusSharedModels\\": "shared-models/src/"
        }
    }
}
```

## Usage

The shared User model includes:
- Basic Laravel authentication features
- Passport API tokens
- Common relationships (providers, notificationProfile)
- Merged attributes from both projects
- Gravatar profile images
- Password hashing

## Optional Dependencies

Some features require additional packages. Install them in your Laravel project if needed:

```bash
# For auditing functionality
composer require owen-it/laravel-auditing

# For secure chat features (if available)
composer require clarus/secure-chat
```

## Adding Project-Specific Features

In each Laravel project, extend the shared model and add project-specific traits:

```php
// Project 1 - with optional traits
use OwenIt\Auditing\Contracts\Auditable;
use Clarus\SecureChat\Traits\SecureChatUser;

class User extends ClarusSharedModels\Models\User implements Auditable
{
    use SecureChatUser;
    use \OwenIt\Auditing\Auditable;
    
    // Project-specific methods
}

// Project 2 - minimal setup
class User extends ClarusSharedModels\Models\User
{
    // Only add traits that exist in this project
    // Project-specific methods
}
```

## Required Models in Your Laravel Project

Your Laravel project should have these models for full functionality:
- `App\Models\Role`
- `App\Models\Partner` 
- `App\Models\RoleUser`
- `App\Models\Provider`
- `App\Models\CallNote`
- `App\Models\NotificationProfile`
- `App\Models\PointOfContact`
- `App\Models\PushToken`
- `App\Notifications\ResetPassword`
