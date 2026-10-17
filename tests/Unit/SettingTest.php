<?php

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

test('returns stored value when key exists', function () {
    Setting::create([
        'key' => 'school_name',
        'value' => 'SMK Negeri 1 Example',
    ]);

    $result = Setting::valueFor('school_name');

    expect($result)->toBe('SMK Negeri 1 Example');
});

test('returns default when key does not exist', function () {
    $result = Setting::valueFor('non_existent_key', 'fallback_default');

    expect($result)->toBe('fallback_default');
});

test('returns null when key does not exist and no default provided', function () {
    $result = Setting::valueFor('another_missing_key');

    expect($result)->toBeNull();
});

test('returns fallback default when stored value is null', function () {
    Setting::create([
        'key' => 'nullable_key',
        'value' => null,
    ]);

    $result = Setting::valueFor('nullable_key', 'fallback_for_null');

    expect($result)->toBe('fallback_for_null');
});

test('preserves empty string without replacing with default', function () {
    Setting::create([
        'key' => 'empty_string_key',
        'value' => '',
    ]);

    $result = Setting::valueFor('empty_string_key', 'default_value');

    expect($result)->toBe('');
});
