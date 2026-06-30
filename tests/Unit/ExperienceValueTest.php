<?php

use App\Support\ExperienceValue;

test('experience value supports month inputs like CV builder', function () {
    expect(ExperienceValue::normalize('6 tháng'))->toBe(0.5)
        ->and(ExperienceValue::normalize('0.5'))->toBe(0.5)
        ->and(ExperienceValue::normalize('1 năm'))->toBe(1.0);
});
