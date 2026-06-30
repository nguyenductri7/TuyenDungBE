<?php

use App\Support\SkillAliasMatcher;

test('skill aliases are canonicalized from shared AI catalog', function () {
    expect(SkillAliasMatcher::matches('JS', 'JavaScript'))->toBeTrue()
        ->and(SkillAliasMatcher::matches('reactjs', 'React'))->toBeTrue()
        ->and(SkillAliasMatcher::matches('postgres', 'PostgreSQL'))->toBeTrue()
        ->and(SkillAliasMatcher::displayName('tailwind'))->toBe('Tailwind CSS');
});

test('different skills do not match by substring only', function () {
    expect(SkillAliasMatcher::matches('Java', 'JavaScript'))->toBeFalse()
        ->and(SkillAliasMatcher::matches('React', 'React Native'))->toBeFalse();
});
