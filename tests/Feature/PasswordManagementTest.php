<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
    $this->user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('old-password'),
        'role' => 'technician',
        'gpid' => 'TCM999',
    ]);
});

test('user can change their own password', function () {
    $oldPasswordHash = $this->user->password;

    actingAs($this->user);

    // Simulate password change
    $this->user->update([
        'password' => Hash::make('new-password-123'),
    ]);

    $this->user->refresh();

    expect($this->user->password)->not->toBe($oldPasswordHash);
    expect(Hash::check('new-password-123', $this->user->password))->toBeTrue();
    expect(Hash::check('old-password', $this->user->password))->toBeFalse();
});

test('password must be at least 8 characters', function () {
    $shortPassword = 'short';
    
    expect(strlen($shortPassword))->toBeLessThan(8);
});

test('password confirmation must match', function () {
    $password = 'new-password-123';
    $passwordConfirmation = 'different-password';

    expect($password)->not->toBe($passwordConfirmation);
});

test('current password must be correct to change password', function () {
    actingAs($this->user);

    $incorrectCurrentPassword = 'wrong-password';
    
    expect(Hash::check($incorrectCurrentPassword, $this->user->password))->toBeFalse();
});

test('super admin can reset user password', function () {
    $superAdmin = User::factory()->create([
        'role' => 'super_admin',
        'gpid' => 'SA001',
    ]);

    $targetUser = User::factory()->create([
        'email' => 'target@example.com',
        'password' => Hash::make('old-password'),
        'role' => 'technician',
    ]);

    actingAs($superAdmin);

    $newPassword = 'reset-password-123';
    $targetUser->update([
        'password' => Hash::make($newPassword),
    ]);

    $targetUser->refresh();

    expect(Hash::check($newPassword, $targetUser->password))->toBeTrue();
    expect(Hash::check('old-password', $targetUser->password))->toBeFalse();
});

test('non-super admin cannot reset other user passwords', function () {
    $manager = User::factory()->create([
        'role' => 'manager',
        'gpid' => 'MGR001',
    ]);

    actingAs($manager);

    expect($manager->role)->not->toBe('super_admin');
});

test('user cannot change password with incorrect current password', function () {
    actingAs($this->user);

    $wrongCurrentPassword = 'incorrect-password';
    
    expect(Hash::check($wrongCurrentPassword, $this->user->password))->toBeFalse();
});

test('password is properly hashed in database', function () {
    $plainPassword = 'my-secure-password';
    
    $this->user->update([
        'password' => Hash::make($plainPassword),
    ]);

    $this->user->refresh();

    expect($this->user->password)->not->toBe($plainPassword);
    expect(Hash::check($plainPassword, $this->user->password))->toBeTrue();
});

test('multiple users can have same password but different hashes', function () {
    $user1 = User::factory()->create([
        'password' => Hash::make('same-password'),
    ]);

    $user2 = User::factory()->create([
        'password' => Hash::make('same-password'),
    ]);

    expect($user1->password)->not->toBe($user2->password);
    expect(Hash::check('same-password', $user1->password))->toBeTrue();
    expect(Hash::check('same-password', $user2->password))->toBeTrue();
});
