<?php

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Enums\ProspectStatus;
use App\Models\Cohort;
use App\Models\Document;
use App\Models\Enrollment;
use App\Models\Prospect;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('admin can upload a document', function () {
    Storage::fake('local');
    $admin = User::factory()->admin()->create();
    $prospect = Prospect::factory()->create(['status' => ProspectStatus::Enrolled]);
    $cohort = Cohort::factory()->create();
    Enrollment::factory()->create(['prospect_id' => $prospect->id, 'cohort_id' => $cohort->id]);

    $file = UploadedFile::fake()->create('contract.pdf', 100, 'application/pdf');

    Livewire::actingAs($admin)
        ->test('pages::prospects.show', ['prospect' => $prospect])
        ->set('documentFile', $file)
        ->set('documentType', DocumentType::EnrollmentAgreement->value)
        ->call('uploadDocument')
        ->assertHasNoErrors();

    expect(Document::where('prospect_id', $prospect->id)->exists())->toBeTrue();
    $document = Document::where('prospect_id', $prospect->id)->first();
    expect($document->type)->toBe(DocumentType::EnrollmentAgreement);
    expect($document->status)->toBe(DocumentStatus::Pending);
    expect($document->original_filename)->toBe('contract.pdf');
    Storage::disk('local')->assertExists($document->disk_path);
});

test('assigned staff can upload a document', function () {
    Storage::fake('local');
    $staff = User::factory()->staff()->create();
    $prospect = Prospect::factory()->create(['status' => ProspectStatus::New, 'assigned_to' => $staff->id]);

    $file = UploadedFile::fake()->create('id-card.jpg', 50, 'image/jpeg');

    Livewire::actingAs($staff)
        ->test('pages::prospects.show', ['prospect' => $prospect])
        ->set('documentFile', $file)
        ->set('documentType', DocumentType::StudentId->value)
        ->call('uploadDocument')
        ->assertHasNoErrors();

    expect(Document::where('prospect_id', $prospect->id)->exists())->toBeTrue();
});

test('unassigned staff cannot upload a document', function () {
    Storage::fake('local');
    $staff = User::factory()->staff()->create();
    $assignedStaff = User::factory()->staff()->create();
    $prospect = Prospect::factory()->create(['status' => ProspectStatus::New, 'assigned_to' => $assignedStaff->id]);

    // Unassigned staff cannot even view this prospect, so component mount is forbidden
    Livewire::actingAs($staff)
        ->test('pages::prospects.show', ['prospect' => $prospect])
        ->assertForbidden();
});

test('upload requires a file and type', function () {
    $admin = User::factory()->admin()->create();
    $prospect = Prospect::factory()->create();

    Livewire::actingAs($admin)
        ->test('pages::prospects.show', ['prospect' => $prospect])
        ->call('uploadDocument')
        ->assertHasErrors(['documentFile', 'documentType']);
});

test('upload rejects invalid file types', function () {
    Storage::fake('local');
    $admin = User::factory()->admin()->create();
    $prospect = Prospect::factory()->create();

    $file = UploadedFile::fake()->create('malware.exe', 100, 'application/x-msdownload');

    Livewire::actingAs($admin)
        ->test('pages::prospects.show', ['prospect' => $prospect])
        ->set('documentFile', $file)
        ->set('documentType', DocumentType::Other->value)
        ->call('uploadDocument')
        ->assertHasErrors(['documentFile']);
});

test('upload auto-links enrollment_id for enrollment agreement type', function () {
    Storage::fake('local');
    $admin = User::factory()->admin()->create();
    $prospect = Prospect::factory()->create(['status' => ProspectStatus::Enrolled]);
    $cohort = Cohort::factory()->create();
    $enrollment = Enrollment::factory()->create(['prospect_id' => $prospect->id, 'cohort_id' => $cohort->id]);

    $file = UploadedFile::fake()->create('agreement.pdf', 100, 'application/pdf');

    Livewire::actingAs($admin)
        ->test('pages::prospects.show', ['prospect' => $prospect])
        ->set('documentFile', $file)
        ->set('documentType', DocumentType::EnrollmentAgreement->value)
        ->call('uploadDocument')
        ->assertHasNoErrors();

    $document = Document::where('prospect_id', $prospect->id)->first();
    expect($document->enrollment_id)->toBe($enrollment->id);
});

test('non-enrollment types have null enrollment_id', function () {
    Storage::fake('local');
    $admin = User::factory()->admin()->create();
    $prospect = Prospect::factory()->create(['status' => ProspectStatus::Enrolled]);
    $cohort = Cohort::factory()->create();
    Enrollment::factory()->create(['prospect_id' => $prospect->id, 'cohort_id' => $cohort->id]);

    $file = UploadedFile::fake()->create('photo.jpg', 50, 'image/jpeg');

    Livewire::actingAs($admin)
        ->test('pages::prospects.show', ['prospect' => $prospect])
        ->set('documentFile', $file)
        ->set('documentType', DocumentType::StudentId->value)
        ->call('uploadDocument')
        ->assertHasNoErrors();

    $document = Document::where('prospect_id', $prospect->id)->first();
    expect($document->enrollment_id)->toBeNull();
});

test('admin can approve a document', function () {
    $admin = User::factory()->admin()->create();
    $prospect = Prospect::factory()->create();
    $document = Document::factory()->create([
        'prospect_id' => $prospect->id,
        'uploaded_by' => $admin->id,
    ]);

    Livewire::actingAs($admin)
        ->test('pages::prospects.show', ['prospect' => $prospect])
        ->call('reviewDocument', $document->id, DocumentStatus::Approved->value)
        ->assertHasNoErrors();

    $document->refresh();
    expect($document->status)->toBe(DocumentStatus::Approved);
    expect($document->reviewed_at)->not->toBeNull();
    expect($document->reviewed_by)->toBe($admin->id);
});

test('admin can reject a document', function () {
    $admin = User::factory()->admin()->create();
    $prospect = Prospect::factory()->create();
    $document = Document::factory()->create([
        'prospect_id' => $prospect->id,
        'uploaded_by' => $admin->id,
    ]);

    Livewire::actingAs($admin)
        ->test('pages::prospects.show', ['prospect' => $prospect])
        ->call('reviewDocument', $document->id, DocumentStatus::Rejected->value)
        ->assertHasNoErrors();

    expect($document->fresh()->status)->toBe(DocumentStatus::Rejected);
});

test('staff cannot review a document', function () {
    $staff = User::factory()->staff()->create();
    $prospect = Prospect::factory()->create(['assigned_to' => $staff->id]);
    $document = Document::factory()->create([
        'prospect_id' => $prospect->id,
        'uploaded_by' => $staff->id,
    ]);

    Livewire::actingAs($staff)
        ->test('pages::prospects.show', ['prospect' => $prospect])
        ->call('reviewDocument', $document->id, DocumentStatus::Approved->value)
        ->assertForbidden();
});

test('admin can delete a document', function () {
    Storage::fake('local');
    $admin = User::factory()->admin()->create();
    $prospect = Prospect::factory()->create();

    $path = 'documents/test-file.pdf';
    Storage::disk('local')->put($path, 'test content');

    $document = Document::factory()->create([
        'prospect_id' => $prospect->id,
        'uploaded_by' => $admin->id,
        'disk_path' => $path,
    ]);

    Livewire::actingAs($admin)
        ->test('pages::prospects.show', ['prospect' => $prospect])
        ->call('deleteDocument', $document->id)
        ->assertHasNoErrors();

    expect(Document::find($document->id))->toBeNull();
    Storage::disk('local')->assertMissing($path);
});

test('staff cannot delete a document', function () {
    $staff = User::factory()->staff()->create();
    $prospect = Prospect::factory()->create(['assigned_to' => $staff->id]);
    $document = Document::factory()->create([
        'prospect_id' => $prospect->id,
        'uploaded_by' => $staff->id,
    ]);

    Livewire::actingAs($staff)
        ->test('pages::prospects.show', ['prospect' => $prospect])
        ->call('deleteDocument', $document->id)
        ->assertForbidden();
});

test('authorized user can download a document', function () {
    Storage::fake('local');
    $admin = User::factory()->admin()->create();
    $prospect = Prospect::factory()->create();

    $path = 'documents/download-test.pdf';
    Storage::disk('local')->put($path, 'pdf content here');

    $document = Document::factory()->create([
        'prospect_id' => $prospect->id,
        'uploaded_by' => $admin->id,
        'disk_path' => $path,
        'original_filename' => 'my-document.pdf',
    ]);

    $this->actingAs($admin)
        ->get(route('documents.download', $document))
        ->assertOk()
        ->assertDownload('my-document.pdf');
});

test('unauthorized user cannot download a document', function () {
    Storage::fake('local');
    $staff = User::factory()->staff()->create();
    $otherStaff = User::factory()->staff()->create();
    $prospect = Prospect::factory()->create(['assigned_to' => $otherStaff->id]);

    $path = 'documents/forbidden-test.pdf';
    Storage::disk('local')->put($path, 'pdf content here');

    $document = Document::factory()->create([
        'prospect_id' => $prospect->id,
        'uploaded_by' => $otherStaff->id,
        'disk_path' => $path,
    ]);

    $this->actingAs($staff)
        ->get(route('documents.download', $document))
        ->assertForbidden();
});
