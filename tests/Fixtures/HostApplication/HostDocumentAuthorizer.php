<?php

namespace LBHurtado\XDocumentLaravel\Tests\Fixtures\HostApplication;

use Illuminate\Contracts\Auth\Authenticatable;

interface HostDocumentAuthorizer
{
    public function assertMayView(?Authenticatable $actor, string $documentId): void;
}
