<?php

use Synchro\Violation\Models\Violation;

it('can create a model', function () {
    config()->set('violations.use_database', true);
    $model = Violation::factory()->create();
    $this->assertModelExists($model);
    expect($model)
        ->toBeInstanceOf(Violation::class)
        ->and($model->id)->toBeGreaterThan(0)
        ->and($model->created_at)->not()->toBeNull()
        ->and($model->updated_at)->not()->toBeNull();
});
