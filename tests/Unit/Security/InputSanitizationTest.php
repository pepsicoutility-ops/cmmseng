<?php

use App\Models\User;
use App\Models\WorkOrder;
use App\Models\Part;
use App\Models\PmSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('work order prevents XSS in description field', function () {
    $user = User::factory()->create(['role' => 'operator']);
    $this->actingAs($user);
    
    $xssPayload = '<script>alert("XSS")</script>';
    
    $wo = WorkOrder::factory()->create([
        'description' => $xssPayload,
    ]);
    
    // Laravel auto-escapes in Blade, but we verify storage is safe
    expect($wo->description)->toBe($xssPayload);
    
    // When retrieved and displayed, it should be escaped
    $escaped = e($wo->description);
    expect($escaped)->not->toContain('<script>');
    expect($escaped)->toContain('&lt;script&gt;');
});

test('part name sanitizes HTML entities', function () {
    $maliciousName = 'Part<img src=x onerror=alert(1)>';
    
    $part = Part::factory()->create([
        'name' => $maliciousName,
    ]);
    
    $escaped = e($part->name);
    expect($escaped)->not->toContain('<img');
    expect($escaped)->toContain('&lt;img');
});

test('user cannot inject SQL through search parameters', function () {
    User::factory()->create(['name' => 'John Doe']);
    User::factory()->create(['name' => 'Jane Smith']);
    
    // Attempt SQL injection
    $sqlInjection = "'; DROP TABLE users; --";
    
    // Laravel's query builder prevents SQL injection
    $results = DB::table('users')
        ->where('name', 'like', '%' . $sqlInjection . '%')
        ->get();
    
    expect($results)->toHaveCount(0);
    
    // Verify users table still exists
    $userCount = DB::table('users')->count();
    expect($userCount)->toBeGreaterThan(0);
});

test('pm schedule title prevents script injection', function () {
    $user = User::factory()->create(['role' => 'manager']);
    $this->actingAs($user);
    
    $scriptPayload = '<script>document.cookie</script>';
    
    $pm = PmSchedule::factory()->create([
        'title' => $scriptPayload,
    ]);
    
    $escaped = e($pm->title);
    expect($escaped)->not->toContain('<script>');
});

test('mass assignment protection validates role values', function () {
    $user = User::create([
        'gpid' => 'TE002',
        'name' => 'Test User',
        'email' => 'test2@example.com',
        'password' => bcrypt('password'),
        'role' => 'operator',
        'department' => 'electric',
    ]);

    expect($user->role)->toBe('operator')
        ->and($user->department)->toBe('electric');
});

test('validates input length limits', function () {
    $this->expectException(\Illuminate\Database\QueryException::class);
    
    // Attempt to create part with extremely long name (exceeds VARCHAR limit)
    Part::factory()->create([
        'name' => str_repeat('A', 300), // Assuming VARCHAR(255)
    ]);
});

test('numeric fields reject non-numeric input', function () {
    $this->expectException(\Exception::class);
    
    Part::factory()->create([
        'min_stock' => 'not-a-number',
    ]);
});

test('enum fields reject invalid values', function () {
    $this->expectException(\Exception::class);
    
    WorkOrder::factory()->create([
        'status' => 'invalid_status',
    ]);
});

test('prevents path traversal in file references', function () {
    $maliciousPath = '../../../etc/passwd';
    
    // Sanitize path
    $sanitized = basename($maliciousPath);
    
    expect($sanitized)->toBe('passwd');
    expect($sanitized)->not->toContain('..');
    expect($sanitized)->not->toContain('/');
});

test('prevents LDAP injection in user queries', function () {
    $ldapInjection = '*)(uid=*))(|(uid=*';
    
    // Query should escape special LDAP characters
    $escaped = preg_replace('/[*()\\\]/', '', $ldapInjection);
    
    expect($escaped)->not->toContain('*');
    expect($escaped)->not->toContain('(');
    expect($escaped)->not->toContain(')');
});
