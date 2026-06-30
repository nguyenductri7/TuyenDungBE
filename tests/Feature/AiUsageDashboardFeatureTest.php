<?php

use App\Models\AiUsageLog;
use App\Models\NguoiDung;

it('returns ai usage overview, feature list and filtered logs for admin', function () {
    $admin = NguoiDung::factory()->admin()->create();
    $candidate = NguoiDung::factory()->ungVien()->create();

    AiUsageLog::create([
        'feature' => 'career_chat',
        'endpoint' => '/chat/career-consultant',
        'provider' => 'template',
        'model_version' => 'chatbot_v1::intent_template',
        'status' => AiUsageLog::STATUS_SUCCESS,
        'used_fallback' => false,
        'duration_ms' => 120,
        'user_id' => $candidate->id,
        'metadata_json' => ['intent' => 'career_path_simulator'],
    ]);
    AiUsageLog::create([
        'feature' => 'cover_letter',
        'endpoint' => '/generate/cover-letter',
        'provider' => 'local_fallback',
        'model_version' => 'fallback',
        'status' => AiUsageLog::STATUS_FALLBACK,
        'used_fallback' => true,
        'duration_ms' => null,
        'user_id' => $candidate->id,
        'error_message' => 'AI service unavailable',
    ]);

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/ai-usage/overview?days=7')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.summary.total_requests', 2)
        ->assertJsonPath('data.summary.fallback_count', 1);

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/ai-usage/features')
        ->assertOk()
        ->assertJsonFragment(['feature' => 'career_chat'])
        ->assertJsonFragment(['feature' => 'cover_letter']);

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/ai-usage/logs?feature=cover_letter&used_fallback=1')
        ->assertOk()
        ->assertJsonPath('data.data.0.feature', 'cover_letter')
        ->assertJsonPath('data.data.0.used_fallback', true);
});

it('forbids non-admin from ai usage dashboard', function () {
    $candidate = NguoiDung::factory()->ungVien()->create();

    $this->actingAs($candidate, 'sanctum')
        ->getJson('/api/v1/admin/ai-usage/overview')
        ->assertForbidden();
});
