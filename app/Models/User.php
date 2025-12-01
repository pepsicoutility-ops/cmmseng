<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser; 
use Filament\Panel;

/**
 * User Model
 * 
 * Represents a system user with role-based access control.
 * Supports 6 roles: super_admin, manager, asisten_manager, technician, tech_store, operator.
 * Uses GPID (Global Person ID) for authentication instead of traditional email.
 * 
 * @property int $id Primary key
 * @property string $gpid Global Person ID (unique identifier, used for login)
 * @property string $name Full name
 * @property string|null $email Email address (optional)
 * @property string $password Hashed password
 * @property string $role User role (super_admin/manager/asisten_manager/technician/tech_store/operator)
 * @property string|null $department Department (Mechanic/Electric/Utility)
 * @property string|null $phone Phone number
 * @property bool $is_active Whether user account is active
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * 
 * @property-read \Illuminate\Database\Eloquent\Collection|PmSchedule[] $pmSchedulesAssigned PM schedules assigned to this user
 * @property-read \Illuminate\Database\Eloquent\Collection|PmSchedule[] $pmSchedulesCreated PM schedules created by this user
 * @property-read \Illuminate\Database\Eloquent\Collection|PmExecution[] $pmExecutions PM executions performed by this user
 * @property-read \Illuminate\Database\Eloquent\Collection|WorkOrder[] $workOrdersCreated Work orders created by this user
 * @property-read \Illuminate\Database\Eloquent\Collection|ActivityLog[] $activityLogs
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * 
 * @method static \Database\Factories\UserFactory factory(int|array|null $count = null)
 * @method static \Illuminate\Database\Eloquent\Builder|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User query()
 * @method static \Illuminate\Database\Eloquent\Builder|User whereRole(string $role)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereDepartment(string $department)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereIsActive(bool $isActive)
 * 
 * @package App\Models
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, \App\Traits\LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'gpid',
        'name',
        'email',
        'password',
        'role',
        'department',
        'phone',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    // Relationships
    
    /**
     * Get all PM schedules assigned to this technician
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<PmSchedule>
     */
public function canAccessPanel(Panel $panel): bool
{ 
    {
      return $this->hasRole('super_admin', 'manager', 'asisten_manager', 'technician', 'tech_store', 'operator');
    }
}
    public function pmSchedulesAssigned()
    {
        return $this->hasMany(PmSchedule::class, 'assigned_to_gpid', 'gpid');
    }

    public function pmSchedulesCreated()
    {
        return $this->hasMany(PmSchedule::class, 'assigned_by_gpid', 'gpid');
    }

    public function pmExecutions()
    {
        return $this->hasMany(PmExecution::class, 'executed_by_gpid', 'gpid');
    }

    public function workOrdersCreated()
    {
        return $this->hasMany(WorkOrder::class, 'created_by_gpid', 'gpid');
    }

    public function woProcesses()
    {
        return $this->hasMany(WoProcesse::class, 'performed_by_gpid', 'gpid');
    }

    public function inventoryMovements()
    {
        return $this->hasMany(InventoryMovement::class, 'performed_by_gpid', 'gpid');
    }

    // Helper methods
    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isManager(): bool
    {
        return $this->role === 'manager';
    }

    public function isAsistenManager(): bool
    {
        return $this->role === 'asisten_manager';
    }

    public function isTechnician(): bool
    {
        return $this->role === 'technician';
    }

    public function isTechStore(): bool
    {
        return $this->role === 'tech_store';
    }

    public function isOperator(): bool
    {
        return $this->role === 'operator';
    }

    // Department helpers
    public function isUtilityDepartment(): bool
    {
        return $this->department === 'Utility';
    }

    public function isMechanicDepartment(): bool
    {
        return $this->department === 'Mechanic';
    }

    public function isElectricDepartment(): bool
    {
        return $this->department === 'Electric';
    }

    // Combined access helpers
    public function canAccessUtilityPerformance(): bool
    {
        return $this->isUtilityDepartment() || 
               in_array($this->role, ['super_admin', 'manager', 'asisten_manager']);
    }
}
