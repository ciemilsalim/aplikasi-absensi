<?php

use App\Models\Notification;
use App\Models\User;

test('authenticated user can mark their own notification as read via web redirect', function () {
    $user = User::factory()->create();
    $notification = Notification::create([
        'user_id' => $user->id,
        'title' => 'Pengingat Presensi',
        'message' => 'Silakan lakukan presensi masuk hari ini.',
        'is_read' => false,
    ]);

    $response = $this
        ->actingAs($user)
        ->from('/dashboard')
        ->post(route('notifications.read', $notification));

    $response->assertRedirect('/dashboard');

    $notification->refresh();
    expect((bool) $notification->is_read)->toBeTrue();
});

test('authenticated user can mark their own notification as read via json request with constrained fields', function () {
    $user = User::factory()->create();
    $notification = Notification::create([
        'user_id' => $user->id,
        'title' => 'Pengingat Presensi JSON',
        'message' => 'Notifikasi uji JSON.',
        'is_read' => false,
    ]);

    $response = $this
        ->actingAs($user)
        ->postJson(route('notifications.read', $notification));

    $response
        ->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Notifikasi berhasil ditandai sudah dibaca.',
            'notification' => [
                'id' => $notification->id,
                'title' => 'Pengingat Presensi JSON',
                'is_read' => true,
            ]
        ])
        ->assertJsonMissingPath('notification.user_id')
        ->assertJsonMissingPath('notification.created_at');

    $notification->refresh();
    expect((bool) $notification->is_read)->toBeTrue();
});

test('guest cannot mark notification as read', function () {
    $user = User::factory()->create();
    $notification = Notification::create([
        'user_id' => $user->id,
        'title' => 'Pesan Rahasia',
        'message' => 'Hanya untuk pemilik akun.',
        'is_read' => false,
    ]);

    $response = $this->post(route('notifications.read', $notification));

    $response->assertRedirect('/login');

    $notification->refresh();
    expect((bool) $notification->is_read)->toBeFalse();
});

test('user cannot mark another users notification as read (idor prevention)', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();

    $notification = Notification::create([
        'user_id' => $owner->id,
        'title' => 'Notifikasi Privat Pemilik',
        'message' => 'Pesan khusus pemilik akun.',
        'is_read' => false,
    ]);

    $response = $this
        ->actingAs($otherUser)
        ->post(route('notifications.read', $notification));

    $response->assertStatus(403);

    $notification->refresh();
    expect((bool) $notification->is_read)->toBeFalse();
});

test('marking non existent notification as read returns 404', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->post('/notifications/999999/read');

    $response->assertStatus(404);
});

test('marking already read notification is idempotent', function () {
    $user = User::factory()->create();
    $notification = Notification::create([
        'user_id' => $user->id,
        'title' => 'Notifikasi Sudah Dibaca',
        'message' => 'Pesan sudah dibaca sebelumnya.',
        'is_read' => true,
    ]);

    // Request berulang kali tetap sukses dan aman
    $response = $this
        ->actingAs($user)
        ->postJson(route('notifications.read', $notification));

    $response
        ->assertOk()
        ->assertJson([
            'success' => true,
            'notification' => [
                'id' => $notification->id,
                'is_read' => true,
            ]
        ]);
});

test('authenticated user can mark all of their unread notifications as read with accurate updated_count', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    // 2 notifikasi belum dibaca untuk User A
    $notifA1 = Notification::create([
        'user_id' => $userA->id,
        'title' => 'Notif A1',
        'message' => 'Pesan A1',
        'is_read' => false,
    ]);
    $notifA2 = Notification::create([
        'user_id' => $userA->id,
        'title' => 'Notif A2',
        'message' => 'Pesan A2',
        'is_read' => false,
    ]);

    // 1 notifikasi belum dibaca untuk User B
    $notifB = Notification::create([
        'user_id' => $userB->id,
        'title' => 'Notif B',
        'message' => 'Pesan B',
        'is_read' => false,
    ]);

    $response = $this
        ->actingAs($userA)
        ->postJson(route('notifications.mark_all_read'));

    $response
        ->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Semua notifikasi berhasil ditandai sudah dibaca.',
            'updated_count' => 2,
        ]);

    $notifA1->refresh();
    $notifA2->refresh();
    $notifB->refresh();

    expect((bool) $notifA1->is_read)->toBeTrue()
        ->and((bool) $notifA2->is_read)->toBeTrue()
        ->and((bool) $notifB->is_read)->toBeFalse(); // Notifikasi User B tetap belum dibaca (tenant isolation)
});

test('repeated mark all read request is idempotent and returns updated_count zero', function () {
    $user = User::factory()->create();

    // Panggilan pertama saat tidak ada notifikasi unread
    $response = $this
        ->actingAs($user)
        ->postJson(route('notifications.mark_all_read'));

    $response
        ->assertOk()
        ->assertJson([
            'success' => true,
            'updated_count' => 0,
        ]);
});

test('guest cannot mark all notifications as read', function () {
    $response = $this->post(route('notifications.mark_all_read'));

    $response->assertRedirect('/login');
});
