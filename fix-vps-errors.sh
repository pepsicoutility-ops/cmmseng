#!/bin/bash
# VPS Error Fixes Script

cd /var/www/cmmseng

# Fix 1: Create WhatsAppSetting model
cat > app/Models/WhatsAppSetting.php << 'EOF'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppSetting extends Model
{
    protected $table = 'whats_app_settings';
    
    protected $fillable = [
        'api_url',
        'api_token',
        'session',
        'group_id',
        'enabled',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];
}
EOF

# Fix 2: Fix AreaPolicy to handle multiple model types
cat > app/Policies/AreaPolicy.php << 'EOF'
<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AreaPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'manager']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Model $model): bool
    {
        return in_array($user->role, ['super_admin', 'manager']);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'manager']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Model $model): bool
    {
        return in_array($user->role, ['super_admin', 'manager']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Model $model): bool
    {
        return $user->role === 'super_admin';
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Model $model): bool
    {
        return $user->role === 'super_admin';
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Model $model): bool
    {
        return $user->role === 'super_admin';
    }
}
EOF

# Fix 3: Create migration for whats_app_settings table if it doesn't exist
mysql -u cmms_user -p'Cmms@SecureDB2025!' cmms_production << 'SQLEOF'
CREATE TABLE IF NOT EXISTS whats_app_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    api_url VARCHAR(255),
    api_token VARCHAR(255),
    session VARCHAR(255) DEFAULT 'default',
    group_id VARCHAR(255),
    enabled TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
SQLEOF

# Fix 4: Set proper permissions
chown www-data:www-data app/Models/WhatsAppSetting.php
chown www-data:www-data app/Policies/AreaPolicy.php

# Fix 5: Clear caches
sudo -u www-data php artisan optimize:clear

echo "Fixes applied successfully!"
