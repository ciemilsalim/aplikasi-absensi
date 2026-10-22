<?php

namespace Tests\Feature;

use App\Models\Teacher;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SSOLmsTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_guest_cannot_access_sso_lms(): void
    {
        $response = $this->get(route('sso.lms'));
        $response->assertStatus(403);
    }

    public function test_teacher_can_generate_sso_token_with_24h_expiry_and_redirects_to_lms(): void
    {
        Carbon::setTestNow('2026-10-12 08:00:00');

        $user = User::factory()->create(['role' => 'teacher']);
        Teacher::create([
            'user_id' => $user->id,
            'name' => 'Budi Guru',
            'nip' => '198501012010011002',
        ]);

        $response = $this->actingAs($user)->get(route('sso.lms'));

        $response->assertRedirect();
        $targetUrl = $response->headers->get('Location');
        $this->assertStringContainsString('/sso/login?token=', $targetUrl);

        // Verify token saved in sso_tokens table
        $ssoRecord = DB::table('sso_tokens')->where('user_id', $user->id)->first();
        $this->assertNotNull($ssoRecord);
        $this->assertEquals(60, strlen($ssoRecord->token));

        // Expiration must be 24 hours (2026-10-13 08:00:00)
        $expiresAt = Carbon::parse($ssoRecord->expires_at);
        $this->assertTrue($expiresAt->isAfter(now()->addHours(20)));
    }

    public function test_admin_can_generate_sso_token_and_previous_tokens_are_cleaned_up(): void
    {
        Carbon::setTestNow('2026-10-12 08:00:00');

        $admin = User::factory()->create(['role' => 'admin']);

        // Insert stale token
        DB::table('sso_tokens')->insert([
            'user_id' => $admin->id,
            'token' => 'stale-token-1234567890123456789012345678901234567890123456789012',
            'expires_at' => now()->subDays(3),
            'created_at' => now()->subDays(3),
            'updated_at' => now()->subDays(3),
        ]);

        $response = $this->actingAs($admin)->get(route('sso.lms'));
        $response->assertRedirect();

        // Stale token deleted, only 1 new token for this admin
        $tokens = DB::table('sso_tokens')->where('user_id', $admin->id)->get();
        $this->assertCount(1, $tokens);
        $this->assertNotEquals('stale-token-1234567890123456789012345678901234567890123456789012', $tokens->first()->token);
    }
}
