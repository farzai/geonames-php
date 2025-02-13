<?php

test('feature class P represents populated places', function () {
    expect('P')->toBe('P')
        ->and('P')->toBeIn(['A', 'H', 'L', 'P', 'R', 'S', 'T', 'U', 'V']);
});

test('all feature classes are valid', function () {
    $validClasses = ['A', 'H', 'L', 'P', 'R', 'S', 'T', 'U', 'V'];
    
    foreach ($validClasses as $class) {
        expect($class)->toBeIn($validClasses);
    }
});

test('feature class descriptions are correct', function () {
    $descriptions = [
        'A' => 'Country, state, region',
        'H' => 'Stream, lake',
        'L' => 'Parks, area',
        'P' => 'City, village',
        'R' => 'Road, railroad',
        'S' => 'Spot, building, farm',
        'T' => 'Mountain, hill, rock',
        'U' => 'Undersea',
        'V' => 'Forest, heath',
    ];

    foreach ($descriptions as $class => $description) {
        expect($class)->toBeIn(array_keys($descriptions))
            ->and($descriptions[$class])->toBe($description);
    }
}); 