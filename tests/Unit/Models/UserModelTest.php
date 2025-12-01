<?php

use App\Models\User;
use App\Models\PmSchedule;
use App\Models\PmExecution;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create([
        'gpid' => 'TEST001',
        'role' => 'technician',
        'department' => 'mechanic',
    ]);
});

test('user has correct fillable attributes', function () {
    $fillable = [
        'gpid', 'name', 'email', 'password', 'role', 'department', 'phone', 'is_active'
    ];
    
    expect($this->user->getFillable())->toBe($fillable);
});

test('user password is hidden from array', function () {
    expect($this->user->toArray())->not->toHaveKey('password');
});

test('user role is cast to string', function () {
    expect($this->user->role)->toBeString();
});

test('user is_active is cast to boolean', function () {
    expect($this->user->is_active)->toBeBool();
});

test('user can check if super admin', function () {
    $this->user->role = 'super_admin';
    expect($this->user->isSuperAdmin())->toBeTrue();
    
    $this->user->role = 'manager';
    expect($this->user->isSuperAdmin())->toBeFalse();
});

test('user can check if manager', function () {
    $this->user->role = 'manager';
    expect($this->user->isManager())->toBeTrue();
    
    $this->user->role = 'technician';
    expect($this->user->isManager())->toBeFalse();
});

test('user can check if asisten manager', function () {
    $this->user->role = 'asisten_manager';
    expect($this->user->isAsistenManager())->toBeTrue();
    
    $this->user->role = 'technician';
    expect($this->user->isAsistenManager())->toBeFalse();
});

test('user can check if technician', function () {
    $this->user->role = 'technician';
    expect($this->user->isTechnician())->toBeTrue();
    
    $this->user->role = 'manager';
    expect($this->user->isTechnician())->toBeFalse();
});

test('user can check if tech store', function () {
    $this->user->role = 'tech_store';
    expect($this->user->isTechStore())->toBeTrue();
    
    $this->user->role = 'technician';
    expect($this->user->isTechStore())->toBeFalse();
});

test('user can check if operator', function () {
    $this->user->role = 'operator';
    expect($this->user->isOperator())->toBeTrue();
    
    $this->user->role = 'technician';
    expect($this->user->isOperator())->toBeFalse();
});

test('user has pm schedules assigned relationship', function () {
    expect($this->user->pmSchedulesAssigned())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
});

test('user has pm schedules created relationship', function () {
    expect($this->user->pmSchedulesCreated())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
});

test('user has pm executions relationship', function () {
    expect($this->user->pmExecutions())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
});

test('user has work orders created relationship', function () {
    expect($this->user->workOrdersCreated())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
});

test('user has wo processes relationship', function () {
    expect($this->user->woProcesses())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
});

test('user has inventory movements relationship', function () {
    expect($this->user->inventoryMovements())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
});
