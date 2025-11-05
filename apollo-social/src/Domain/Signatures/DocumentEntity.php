<?php
namespace Apollo\Domain\Signatures;

/**
 * Document entity (stub)
 *
 * Represents a document that needs to be signed.
 * TODO: Define document properties, signature workflow and DocuSeal integration.
 */
class DocumentEntity
{
    /**
     * Document ID
     * TODO: implement property and getters/setters
     */
    protected $id;

    /**
     * DocuSeal template ID
     * TODO: implement DocuSeal integration
     */
    protected $docuseal_template_id;

    /**
     * Signature status
     * TODO: implement signature tracking
     */
    protected $status;

    /**
     * Create signature request
     * TODO: implement DocuSeal API integration for signature requests
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