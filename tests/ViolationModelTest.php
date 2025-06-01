<?php

use Synchro\Violation\Database\Factories\ViolationFactory;
use Synchro\Violation\Models\Violation;

it('can create a violation model', function () {
    $violationData = ViolationFactory::new()->definition();
    $model = Violation::factory()->create($violationData);
    $this->assertModelExists($model);
    expect($model)
        ->toBeInstanceOf(Violation::class)
        ->and($model->id)->toBeGreaterThan(0)
        ->and($model->report)->toBe($violationData['report'])
        ->and($model->user_agent)->toBe($violationData['user_agent'])
        ->and($model->ip)->toBe($violationData['ip'])
        ->and($model->report_source)->toBe($violationData['report_source'])
        ->and($model->forwarded)->toBe($violationData['forwarded'])
        ->and($model->forward_attempts)->toBe($violationData['forward_attempts'])
        ->and($model->created_at)->not()->toBeNull()
        ->and($model->updated_at)->not()->toBeNull();
});
