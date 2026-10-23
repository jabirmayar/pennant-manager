<?php

use daacreators\PennantManager\Models\Feature;
use daacreators\PennantManager\Support\FeatureValue;
use daacreators\PennantManager\Tests\Models\ScopePerson;
use daacreators\PennantManager\Widgets\FeatureAdoptionWidget;

it('counts active configured records using portable value resolution', function () {
    config(['pennant-manager.scope_models' => ['Person' => ScopePerson::class]]);
    ScopePerson::create(['uuid' => '1']);
    ScopePerson::create(['uuid' => '2']);
    foreach ([
        '1' => 'true',
        '2' => FeatureValue::encode(true, activateAt: now()->addDay()->toDateTimeString()),
        'deleted' => 'true',
    ] as $id => $value) {
        Feature::create(['name' => 'dashboard', 'scope' => ScopePerson::class . '|' . $id, 'value' => $value]);
    }
    Feature::create(['name' => 'unknown', 'scope' => 'MissingClass|1', 'value' => 'true']);
    $widget = new class extends FeatureAdoptionWidget
    {
        public function stats(): array
        {
            return $this->getStats();
        }
    };
    $stats = $widget->stats();
    expect($stats)->toHaveCount(1)->and($stats[0]->getValue())->toBe('50%');
});
