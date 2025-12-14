<?php

/**
 * Apollo Documents Manager - Sistema de Documentos e Planilhas.
 *
 * URLs:
 * /doc/new → Criar novo documento.
 * /doc/{file_id} → Editar documento existente.
 * /pla/new → Criar nova planilha.
 * /pla/{file_id} → Editar planilha existente.
 * /sign → Listagem de documentos para assinatura.
 * /sign/{file_id} → Assinar documento específico.
 *
 * @package Apollo\Modules\Documents
 * @since   2.0.0
 *
 * phpcs:disable WordPress.Files.FileName.InvalidClassFileName
 * phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
 * phpcs:disable Squiz.Commenting
 * phpcs:disable WordPress.PHP.YodaConditions.NotYoda
 * phpcs:disable WordPress.DB
 * phpcs:disable Universal.Operators.DisallowShortTernary.Found
 * phpcs:disable WordPressVIPMinimum.Variables
 * phpcs:disable WordPress.Security.ValidatedSanitizedInput
 */

namespace Apollo\Modules\Documents;

class DocumentsManager
{
    private $documents_table;
    private $signatures_table;

    public function __construct()
    {
        global $wpdb;
        $this->documents_table  = $wpdb->prefix . 'apollo_documents';
        $this->signatures_table = $wpdb->prefix . 'apollo_document_signatures';
    }

    /**
     * Create documents tables
     */
    public function createTables()
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Tabela de documentos
        $sql1 = "CREATE TABLE {$this->documents_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            file_id varchar(32) NOT NULL UNIQUE,
            type enum('documento','planilha') NOT NULL,
            title varchar(255) NOT NULL,
            content longtext,
            pdf_path varchar(500),
            status enum('draft','ready','signing','completed') DEFAULT 'draft',
            requires_signatures tinyint(1) DEFAULT 0,
            total_signatures_needed int(2) DEFAULT 2,
            created_by bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_file_id (file_id),
            KEY idx_type (type),
            KEY idx_status (status),
            KEY idx_created_by (created_by)
        ) $charset_collate;";

        // Tabela de assinaturas de documentos
        $sql2 = "CREATE TABLE {$this->signatures_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            document_id bigint(20) NOT NULL,
            signer_party enum('party_a','party_b') NOT NULL,
            signer_name varchar(255),
            signer_cpf varchar(14),
            signer_email varchar(255),
            signature_data text,
            signed_at datetime NULL,
            verification_token varchar(64),
            status enum('pending','signed','declined') DEFAULT 'pending',
            ip_address varchar(50),
            user_agent text,
            metadata longtext,
            PRIMARY KEY (id),
            KEY idx_document_id (document_id),
            KEY idx_signer_party (signer_party),
            KEY idx_status (status),
            KEY idx_verification_token (verification_token),
            FOREIGN KEY (document_id) REFERENCES {$this->documents_table}(id) ON DELETE CASCADE
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql1);
        dbDelta($sql2);
    }

    /**
     * Generate unique file ID
     */
    private function generateFileId()
    {
        return wp_generate_password(16, false);
    }

    /**
     * Create new document
     */
    public function createDocument($type, $title, $content = '', $user_id = null)
    {
        global $wpdb;

        $user_id = $user_id ?: get_current_user_id();
        $file_id = $this->generateFileId();

        $data = [
            'file_id'    => $file_id,
            'type'       => $type,
            'title'      => $title,
            'content'    => $content,
            'status'     => 'draft',
            'created_by' => $user_id,
        ];

        $result = $wpdb->insert($this->documents_table, $data);

        if ($result) {
            return [
                'success'     => true,
                'file_id'     => $file_id,
                'document_id' => $wpdb->insert_id,
                'url'         => $this->getDocumentUrl($type, $file_id),
            ];
        }

        return [
            'success' => false,
            'error'   => 'Failed to create document',
        ];
    }

    /**
     * Get document URL
     */
    private function getDocumentUrl($type, $file_id)
    {
        $prefix = ($type === 'documento') ? 'doc' : 'pla';

        return site_url("/{$prefix}/{$file_id}");
    }

    /**
     * Get document by file ID
     *
     * @param string $file_id The unique file identifier
     * @return array|null Document data or null if not found
     */
    public function getDocumentByFileId(string $file_id): ?array
    {
        global $wpdb;

        $document = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->documents_table} WHERE file_id = %s",
                $file_id
            ),
            ARRAY_A
        );

        if (! $document) {
            return null;
        }

        // Add version from row count or use 1
        $document['version'] = (int) ($document['version'] ?? 1);

        // Get signature count
        $document['signature_count'] = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->signatures_table} WHERE document_id = %d AND status = 'signed'",
                $document['id']
            )
        );

        return $document;
    }

    /**
     * Get document by ID
     *
     * @param int $id The document ID
     * @return array|null Document data or null if not found
     */
    public function getDocumentById(int $id): ?array
    {
        global $wpdb;

        $document = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->documents_table} WHERE id = %d",
                $id
            ),
            ARRAY_A
        );

        return $document ?: null;
    }

    /**
     * Update document
     *
     * @param int   $id   Document ID
     * @param array $data Data to update
     * @return bool Success status
     */
    public function updateDocument(int $id, array $data): bool
    {
        global $wpdb;

        $allowed = [ 'title', 'content', 'status', 'pdf_path', 'requires_signatures', 'total_signatures_needed' ];
        $update  = array_intersect_key($data, array_flip($allowed));

        if (empty($update)) {
            return false;
        }

        $result = $wpdb->update(
            $this->documents_table,
            $update,
            [ 'id' => $id ]
        );

        return $result !== false;
    }

    /**
     * Validate CPF
     */
    public function validateCPF($cpf)
    {
        $cpf = preg_replace('/[^0-9]/', '', $cpf);

        if (strlen($cpf) != 11) {
            return false;
        }

        // Verifica sequência de números iguais
        if (preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }

        // Valida primeiro dígito verificador
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += $cpf[ $i ] * (10 - $i);
        }
        $digit1 = ($sum % 11 < 2) ? 0 : 11 - ($sum % 11);

        if ($cpf[9] != $digit1) {
            return false;
        }

        // Valida segundo dígito verificador
        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $sum += $cpf[ $i ] * (11 - $i);
        }
        $digit2 = ($sum % 11 < 2) ? 0 : 11 - ($sum % 11);

        if ($cpf[10] != $digit2) {
            return false;
        }

        return true;
    }

    /**
     * Validate full name (min 2 words, most > 3 letters)
     */
    public function validateFullName($name)
    {
        $name  = trim($name);
        $words = preg_split('/\s+/', $name);

        // Mínimo 2 palavras
        if (count($words) < 2) {
            return [
                'valid' => false,
                'error' => 'Nome deve ter no mínimo 2 palavras (nome e sobrenome)',
            ];
        }

        // Verificar se maioria das palavras tem mais de 3 letras
        $valid_words = 0;
        foreach ($words as $word) {
            if (strlen($word) > 3) {
                ++$valid_words;
            }
        }

        if ($valid_words < (count($words) / 2)) {
            return [
                'valid' => false,
                'error' => 'Maioria das palavras deve ter mais de 3 letras',
            ];
        }

        // Verificar se contém apenas letras e espaços
        if (! preg_match('/^[a-zA-ZÀ-ÿ\s]+$/', $name)) {
            return [
                'valid' => false,
                'error' => 'Nome deve conter apenas letras',
            ];
        }

        return [ 'valid' => true ];
    }

    /**
     * Prepare document for signing (convert to PDF)
     */
    public function prepareForSigning($document_id)
    {
        global $wpdb;

        $document = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->documents_table} WHERE id = %d", $document_id),
            ARRAY_A
        );

        if (! $document) {
            return [
                'success' => false,
                'error'   => 'Document not found',
            ];
        }

        // Gerar PDF do documento
        $pdf_path = $this->convertToPDF($document);

        if ($pdf_path) {
            $wpdb->update(
                $this->documents_table,
                [
                    'pdf_path'            => $pdf_path,
                    'status'              => 'ready',
                    'requires_signatures' => 1,
                ],
                [ 'id' => $document_id ]
            );

            return [
                'success'  => true,
                'pdf_path' => $pdf_path,
                'sign_url' => site_url("/sign/{$document['file_id']}"),
            ];
        }

        return [
            'success' => false,
            'error'   => 'Failed to generate PDF',
        ];
    }

    /**
     * Convert document to PDF
     */
    private function convertToPDF($document)
    {
        $upload_dir = wp_upload_dir();
        $pdf_dir    = $upload_dir['basedir'] . '/apollo-documents/pdf';

        if (! file_exists($pdf_dir)) {
            wp_mkdir_p($pdf_dir);
        }

        $pdf_filename = $document['file_id'] . '.pdf';
        $pdf_path     = $pdf_dir . '/' . $pdf_filename;

        // TODO: Implementar conversão para PDF
        // Por enquanto, copiar PDF se já existe ou gerar básico

        return '/wp-content/uploads/apollo-documents/pdf/' . $pdf_filename;
    }

    /**
     * Create signature request
     */
    public function createSignatureRequest($document_id, $party, $email, $name = '', $cpf = '')
    {
        global $wpdb;

        $verification_token = wp_generate_password(32, false);

        $data = [
            'document_id'        => $document_id,
            'signer_party'       => $party,
            'signer_email'       => $email,
            'signer_name'        => $name,
            'signer_cpf'         => $cpf,
            'verification_token' => $verification_token,
            'status'             => 'pending',
        ];

        $result = $wpdb->insert($this->signatures_table, $data);

        if ($result) {
            // Enviar e-mail com link de assinatura
            $sign_url = site_url("/sign/{$verification_token}");
            $this->sendSignatureEmail($email, $sign_url, $name);

            return [
                'success'      => true,
                'signature_id' => $wpdb->insert_id,
                'sign_url'     => $sign_url,
            ];
        }

        return [
            'success' => false,
            'error'   => 'Failed to create signature request',
        ];
    }

    /**
     * Send signature email
     */
    private function sendSignatureEmail($email, $sign_url, $name)
    {
        $subject = '[Apollo] Documento aguardando sua assinatura';

        $message = 'Olá' . ($name ? " {$name}" : '') . ",\n\n";
        $message .= "Há um documento aguardando sua assinatura.\n\n";
        $message .= "Clique no link abaixo para visualizar e assinar:\n";
        $message .= $sign_url . "\n\n";
        $message .= "Este link é único e válido por 30 dias.\n\n";
        $message .= 'Apollo Social';

        wp_mail($email, $subject, $message);
    }

    /**
     * Get document completion percentage
     */
    public function getCompletionPercentage($document_id)
    {
        global $wpdb;

        $document = $wpdb->get_row(
            $wpdb->prepare("SELECT total_signatures_needed FROM {$this->documents_table} WHERE id = %d", $document_id),
            ARRAY_A
        );

        if (! $document) {
            return 0;
        }

        $signed_count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->signatures_table} WHERE document_id = %d AND status = 'signed'",
                $document_id
            )
        );

        $total_needed = $document['total_signatures_needed'];

        return ($signed_count / $total_needed) * 100;
    }

    /**
     * Sign document
     */
    public function signDocument($token, $signer_data, $signature_canvas)
    {
        global $wpdb;

        // Validar CPF
        if (! $this->validateCPF($signer_data['cpf'])) {
            return [
                'success' => false,
                'error'   => 'CPF inválido',
            ];
        }

        // Validar nome completo
        $name_validation = $this->validateFullName($signer_data['name']);
        if (! $name_validation['valid']) {
            return [
                'success' => false,
                'error'   => $name_validation['error'],
            ];
        }

        // Buscar signature request
        $signature = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->signatures_table} WHERE verification_token = %s AND status = 'pending'",
                $token
            ),
            ARRAY_A
        );

        if (! $signature) {
            return [
                'success' => false,
                'error'   => 'Token inválido ou já utilizado',
            ];
        }

        // Atualizar assinatura
        $result = $wpdb->update(
            $this->signatures_table,
            [
                'signer_name'    => $signer_data['name'],
                'signer_cpf'     => $signer_data['cpf'],
                'signature_data' => $signature_canvas,
                'signed_at'      => current_time('mysql'),
                'status'         => 'signed',
                'ip_address'     => $_SERVER['REMOTE_ADDR'],
                'user_agent'     => $_SERVER['HTTP_USER_AGENT'],
            ],
            [ 'id' => $signature['id'] ]
        );

        if ($result !== false) {
            // Verificar se documento está completo
            $completion = $this->getCompletionPercentage($signature['document_id']);

            if ($completion >= 100) {
                $wpdb->update(
                    $this->documents_table,
                    [ 'status' => 'completed' ],
                    [ 'id'     => $signature['document_id'] ]
                );
            } else {
                $wpdb->update(
                    $this->documents_table,
                    [ 'status' => 'signing' ],
                    [ 'id'     => $signature['document_id'] ]
                );
            }

            return [
                'success'    => true,
                'completion' => $completion,
                'message'    => 'Documento assinado com sucesso!',
            ];
        }//end if

        return [
            'success' => false,
            'error'   => 'Erro ao registrar assinatura',
        ];
    }
}
