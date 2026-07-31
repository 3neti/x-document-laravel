<?php

namespace LBHurtado\XDocumentLaravel\Tests\Fixtures\HostApplication;

use Illuminate\Contracts\Auth\Authenticatable;

final class RecordingDocumentAuthorizer implements HostDocumentAuthorizer
{
    /** @var list<string> */
    public array $authorizedDocumentIds = [];

    public function assertMayView(?Authenticatable $actor, string $documentId): void
    {
        if ($documentId === 'forbidden') {
            throw new HostDocumentAccessDenied('The example host denied document access.');
        }

        $this->authorizedDocumentIds[] = $documentId;
    }

    public function hasAuthorized(string $documentId): bool
    {
        return in_array($documentId, $this->authorizedDocumentIds, true);
    }
}
