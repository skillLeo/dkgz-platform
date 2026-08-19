<?php

use App\Models\Assessor;
use App\Models\AssessorServiceArea;
use App\Models\Assignment;
use App\Models\AssignmentDocument;
use App\Models\AssignmentStatusEvent;
use App\Models\Commission;
use App\Models\ContentBlock;
use App\Models\CustomerReview;
use App\Models\EmailLog;
use App\Models\EmailTemplate;
use App\Models\Faq;
use App\Models\Invitation;
use App\Models\Page;
use App\Models\PostalCode;
use App\Models\RequestImage;
use App\Models\RequestMatch;
use App\Models\ServiceRequest;
use App\Models\ServiceType;
use App\Models\Setting;
use App\Models\User;

it('can persist every model from its factory', function (string $model) {
    $record = $model::factory()->create();

    expect($record)->toBeInstanceOf($model)
        ->and($record->exists)->toBeTrue()
        ->and($model::query()->whereKey($record->getKey())->exists())->toBeTrue();
})->with([
    User::class,
    ServiceType::class,
    PostalCode::class,
    Assessor::class,
    AssessorServiceArea::class,
    ServiceRequest::class,
    RequestImage::class,
    RequestMatch::class,
    Assignment::class,
    AssignmentDocument::class,
    AssignmentStatusEvent::class,
    Commission::class,
    CustomerReview::class,
    Invitation::class,
    Page::class,
    Faq::class,
    EmailTemplate::class,
    EmailLog::class,
    ContentBlock::class,
    Setting::class,
]);
