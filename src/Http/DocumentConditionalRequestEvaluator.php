<?php

namespace LBHurtado\XDocumentLaravel\Http;

use LBHurtado\XDocumentLaravel\Data\DocumentHttpRequestContext;

final readonly class DocumentConditionalRequestEvaluator
{
    public function isNotModified(
        DocumentHttpRequestContext $request,
        HttpEntityTag $etag,
    ): bool {
        return $request->ifNoneMatch !== null
            && IfNoneMatch::parse($request->ifNoneMatch)->matches($etag);
    }
}
