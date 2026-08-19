<?php

namespace App\Actions;

use App\Models\Assignment;
use App\Models\AssignmentDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Documents land on the private disk under an unguessable name. The MIME type
 * is read from the file's actual content, never from its extension.
 */
class StoreAssignmentDocumentAction
{
    private const ALLOWED = [
        'application/pdf',
        'image/jpeg',
        'image/png',
    ];

    public function execute(Assignment $assignment, UploadedFile $file, string $type): AssignmentDocument
    {
        $mime = $file->getMimeType();

        if (! in_array($mime, self::ALLOWED, true)) {
            throw new RuntimeException('Erlaubt sind PDF, JPG und PNG.');
        }

        $extension = match ($mime) {
            'application/pdf' => 'pdf',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
        };

        $path = sprintf('auftraege/%d/%s.%s', $assignment->id, bin2hex(random_bytes(16)), $extension);

        Storage::disk(AssignmentDocument::DISK)->put($path, file_get_contents($file->getRealPath()));

        // One report and one invoice per assignment: a re-upload replaces the
        // previous file rather than stacking another beside it.
        if (in_array($type, [AssignmentDocument::TYPE_REPORT, AssignmentDocument::TYPE_CUSTOMER_INVOICE], true)) {
            $assignment->documents()->where('type', $type)->get()->each(function (AssignmentDocument $existing) {
                Storage::disk(AssignmentDocument::DISK)->delete($existing->path);
                $existing->delete();
            });
        }

        $document = $assignment->documents()->create([
            'type' => $type,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $mime,
            'size_bytes' => $file->getSize(),
            'uploaded_at' => now(),
        ]);

        if ($assignment->hasRequiredDocuments() && $assignment->status !== Assignment::STATUS_COMPLETED) {
            $previous = $assignment->status;
            $assignment->update(['status' => Assignment::STATUS_DOCUMENTS_UPLOADED]);
            $assignment->recordStatusEvent(
                $previous,
                Assignment::STATUS_DOCUMENTS_UPLOADED,
                'assessor',
                $assignment->assessor->user_id,
            );
        }

        return $document;
    }
}
