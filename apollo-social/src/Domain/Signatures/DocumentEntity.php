<?php

namespace Apollo\Domain\Signatures;

/**
 * Document entity
 *
 * Represents a document that needs to be signed with GOV.BR
 */
class DocumentEntity
{
    /**
     * Document ID
     * TODO: implement property and getters/setters
     */
    protected $id;

    /**
     * GOV.BR template ID
     */
    protected $govbr_template_id;

    /**
     * Signature status
     * TODO: implement signature tracking
     */
    protected $status;

    /**
     * Create signature request
     * Implements GOV.BR API integration for qualified signature requests
     */
    public function createSignatureRequest($signers)
    {
        // TODO: implement signature request creation logic
    }

    /**
     * Check if document is fully signed
     * TODO: implement signature completion checking
     */
    public function isFullySigned()
    {
        // TODO: implement signature completion checking logic
    }
}
