<?php

use App\Actions\AcceptAssignmentAction;
use App\Actions\MatchRequestAction;
use App\Models\Assessor;
use App\Models\AssessorServiceArea;
use App\Models\Assignment;
use App\Models\AssignmentDocument;
use App\Models\ServiceRequest;
use App\Models\ServiceType;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->type = ServiceType::factory()->create();
});

function portalAssessor(ServiceType $type, string $plz = '40589'): Assessor
{
    $user = User::factory()->create(['is_active' => true]);
    $user->assignRole('assessor');

    $assessor = Assessor::factory()->create([
        'user_id' => $user->id,
        'approval_status' => Assessor::STATUS_APPROVED,
        'is_available' => true,
    ]);

    AssessorServiceArea::factory()->covering($plz)->create(['assessor_id' => $assessor->id]);
    $assessor->serviceTypes()->attach($type->id);

    return $assessor->fresh(['user', 'serviceAreas', 'serviceTypes']);
}

describe('customer contact data', function () {
    it('is absent from the payload before acceptance', function () {
        $assessor = portalAssessor($this->type);
        $request = ServiceRequest::factory()->inPostalCode('40589')->create([
            'service_type_id' => $this->type->id,
            'reference' => ServiceRequest::nextReference(),
            'customer_name' => 'Martina Reinhardt',
            'customer_phone' => '+49 179 4480169',
            'customer_email' => 'm.reinhardt@web.test',
        ]);

        app(MatchRequestAction::class)->execute($request);

        $response = $this->actingAs($assessor->user)->get("/portal/anfragen/{$request->id}");

        $response->assertOk()->assertInertia(
            fn ($page) => $page->where('request.customer', null)
                ->where('request.contact_released', false)
        );

        // And provably not anywhere in the raw payload either.
        $response->assertDontSee('Martina Reinhardt')
            ->assertDontSee('m.reinhardt@web.test')
            ->assertDontSee('4480169');
    });

    it('is present once that assessor holds the assignment', function () {
        $assessor = portalAssessor($this->type);
        $request = ServiceRequest::factory()->inPostalCode('40589')->create([
            'service_type_id' => $this->type->id,
            'reference' => ServiceRequest::nextReference(),
            'customer_name' => 'Martina Reinhardt',
            'customer_email' => 'm.reinhardt@web.test',
        ]);

        app(MatchRequestAction::class)->execute($request);
        $assignment = app(AcceptAssignmentAction::class)->execute($request, $assessor);

        $this->actingAs($assessor->user)
            ->get("/portal/auftraege/{$assignment->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('request.contact_released', true)
                ->where('request.customer.name', 'Martina Reinhardt')
                ->where('request.customer.email', 'm.reinhardt@web.test'));
    });

    it('stays hidden from a partner who lost the race', function () {
        $winner = portalAssessor($this->type);
        $loser = portalAssessor($this->type);

        $request = ServiceRequest::factory()->inPostalCode('40589')->create([
            'service_type_id' => $this->type->id,
            'reference' => ServiceRequest::nextReference(),
            'customer_name' => 'Martina Reinhardt',
        ]);

        app(MatchRequestAction::class)->execute($request);
        app(AcceptAssignmentAction::class)->execute($request, $winner);

        $this->actingAs($loser->user)
            ->get("/portal/anfragen/{$request->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('request.customer', null))
            ->assertDontSee('Martina Reinhardt');
    });
});

describe('assignment access', function () {
    it('refuses an assessor another assessor\'s assignment', function () {
        $mine = portalAssessor($this->type);
        $theirs = portalAssessor($this->type);

        $assignment = Assignment::factory()->create(['assessor_id' => $theirs->id]);

        $this->actingAs($mine->user)
            ->get("/portal/auftraege/{$assignment->id}")
            ->assertForbidden();
    });
});

describe('document downloads', function () {
    beforeEach(function () {
        Storage::fake('private');
    });

    it('serves the file to the owning assessor', function () {
        $assessor = portalAssessor($this->type);
        $assignment = Assignment::factory()->create(['assessor_id' => $assessor->id]);

        Storage::disk('private')->put('auftraege/gutachten.pdf', '%PDF-1.4 test');

        $document = AssignmentDocument::factory()->report()->create([
            'assignment_id' => $assignment->id,
            'path' => 'auftraege/gutachten.pdf',
        ]);

        $this->actingAs($assessor->user)
            ->get("/portal/auftraege/{$assignment->id}/dokumente/{$document->id}/download")
            ->assertOk()
            ->assertDownload('Gutachten.pdf');
    });

    it('refuses the file to a different assessor', function () {
        $owner = portalAssessor($this->type);
        $stranger = portalAssessor($this->type);

        $assignment = Assignment::factory()->create(['assessor_id' => $owner->id]);
        Storage::disk('private')->put('auftraege/gutachten.pdf', '%PDF-1.4 test');

        $document = AssignmentDocument::factory()->report()->create([
            'assignment_id' => $assignment->id,
            'path' => 'auftraege/gutachten.pdf',
        ]);

        $this->actingAs($stranger->user)
            ->get("/portal/auftraege/{$assignment->id}/dokumente/{$document->id}/download")
            ->assertForbidden();
    });

    it('refuses the file to a guest', function () {
        $assessor = portalAssessor($this->type);
        $assignment = Assignment::factory()->create(['assessor_id' => $assessor->id]);
        $document = AssignmentDocument::factory()->report()->create(['assignment_id' => $assignment->id]);

        $this->get("/portal/auftraege/{$assignment->id}/dokumente/{$document->id}/download")
            ->assertRedirect(route('login'));
    });

    it('never exposes the storage path in a payload', function () {
        $assessor = portalAssessor($this->type);
        $assignment = Assignment::factory()->create(['assessor_id' => $assessor->id]);

        AssignmentDocument::factory()->report()->create([
            'assignment_id' => $assignment->id,
            'path' => 'auftraege/geheim-pfad-xyz.pdf',
        ]);

        $this->actingAs($assessor->user)
            ->get("/portal/auftraege/{$assignment->id}")
            ->assertOk()
            ->assertDontSee('geheim-pfad-xyz');
    });

    it('refuses a document that belongs to another assignment', function () {
        $assessor = portalAssessor($this->type);
        $mine = Assignment::factory()->create(['assessor_id' => $assessor->id]);
        $other = Assignment::factory()->create(['assessor_id' => $assessor->id]);

        $document = AssignmentDocument::factory()->report()->create(['assignment_id' => $other->id]);

        $this->actingAs($assessor->user)
            ->get("/portal/auftraege/{$mine->id}/dokumente/{$document->id}/download")
            ->assertNotFound();
    });
});
