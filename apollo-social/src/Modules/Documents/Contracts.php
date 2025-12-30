<?php
/**
 * Apollo Documents - Final Contracts
 *
 * Este arquivo documenta os contratos estáveis do módulo Documents.
 * Estas interfaces são consideradas públicas e estáveis após FASE 11.
 *
 * @package Apollo\Modules\Documents
 * @since   2.1.0
 */

declare(strict_types=1);

namespace Apollo\Modules\Documents\Contracts;

/**
 * CONSTANTES CANÔNICAS
 * ====================
 *
 * Use SEMPRE estas constantes, nunca strings literais.
 */

/** CPT registrado para documentos */
const CPT_DOCUMENT = 'apollo_document';

/** Metakey canônico para assinaturas */
const META_SIGNATURES = '_apollo_doc_signatures';

/** Metakey legado (read-only, será removido) */
const META_SIGNATURES_LEGACY = '_apollo_document_signatures';

/** Metakey para estado Apollo (2ª camada) */
const META_STATE = '_apollo_doc_state';

/** Metakey para PDF attachment ID */
const META_PDF = '_apollo_doc_pdf_id';

/** Metakey para hash do documento */
const META_HASH = '_apollo_doc_hash';

/**
 * ESTADOS DO WORKFLOW
 * ===================
 *
 * Layer 1: post_status (WordPress)
 * Layer 2: apollo_doc_state (Apollo)
 */

/** Estados Apollo válidos */
const VALID_STATES = array(
	'draft',
	'pending_review',
	'ready',
	'signing',
	'signed',
	'completed',
	'archived',
	'cancelled',
	'rejected',
);

/**
 * Mapeamento Estado Apollo → post_status
 *
 * IMPORTANTE: signing/signed/ready → private (NÃO publish!)
 */
const STATE_TO_POST_STATUS = array(
	'draft'          => 'draft',
	'pending_review' => 'pending',
	'ready'          => 'private',
	'signing'        => 'private',
	'signed'         => 'private',
	'completed'      => 'publish',
	'archived'       => 'private',
	'cancelled'      => 'draft',
	'rejected'       => 'draft',
);

/**
 * INTERFACE: DocumentRepository
 * =============================
 *
 * Ponto único de acesso para operações de documentos.
 */
interface DocumentRepositoryInterface {

	/**
	 * Cria novo documento.
	 *
	 * @param array $data {
	 *     @type string $title       Título do documento.
	 *     @type string $content     Conteúdo/corpo.
	 *     @type int    $author_id   ID do autor (default: current user).
	 *     @type string $state       Estado inicial (default: 'draft').
	 *     @type int    $group_id    ID do grupo associado (opcional).
	 * }
	 * @return int Post ID criado.
	 * @throws \InvalidArgumentException Se dados inválidos.
	 */
	public static function createDocument( array $data ): int;

	/**
	 * Atualiza documento existente.
	 *
	 * @param int   $post_id ID do documento.
	 * @param array $data    Dados a atualizar.
	 * @return bool Sucesso.
	 * @throws \InvalidArgumentException Se documento não existe.
	 */
	public static function updateDocument( int $post_id, array $data ): bool;

	/**
	 * Transiciona estado do documento.
	 *
	 * @param int    $post_id   ID do documento.
	 * @param string $new_state Novo estado (ver VALID_STATES).
	 * @return bool Sucesso.
	 * @throws \InvalidArgumentException Se transição inválida.
	 */
	public static function transitionStatus( int $post_id, string $new_state ): bool;

	/**
	 * Obtém documento completo.
	 *
	 * @param int $post_id ID do documento.
	 * @return array|null Dados do documento ou null.
	 */
	public static function getDocument( int $post_id ): ?array;

	/**
	 * Armazena assinatura.
	 *
	 * @param int   $post_id   ID do documento.
	 * @param array $signature {
	 *     @type int    $signer_user_id ID do usuário (opcional se guest).
	 *     @type string $signer_cpf     CPF do signatário (obrigatório).
	 *     @type string $signer_name    Nome do signatário.
	 *     @type string $method         Método (cpf, email, etc).
	 *     @type string $ip_address     IP do signatário.
	 * }
	 * @return int Signature ID.
	 * @throws \InvalidArgumentException Se dados inválidos.
	 */
	public static function storeSignature( int $post_id, array $signature ): int;

	/**
	 * Obtém assinaturas do documento.
	 *
	 * @param int $post_id ID do documento.
	 * @return array Lista de assinaturas.
	 */
	public static function getSignatures( int $post_id ): array;

	/**
	 * Anexa PDF ao documento.
	 *
	 * @param int    $post_id  ID do documento.
	 * @param string $pdf_path Caminho do PDF.
	 * @return int Attachment ID.
	 */
	public static function attachPdf( int $post_id, string $pdf_path ): int;
}

/**
 * INTERFACE: DocumentStatus
 * =========================
 *
 * Gerenciamento de status em 2 camadas.
 */
interface DocumentStatusInterface {

	/**
	 * Mapeia estado Apollo para post_status.
	 *
	 * @param string $state Estado Apollo.
	 * @return string Post status.
	 */
	public static function mapToPostStatus( string $state ): string;

	/**
	 * Verifica se transição é válida.
	 *
	 * @param string $from Estado atual.
	 * @param string $to   Estado desejado.
	 * @return bool True se válida.
	 */
	public static function isValidTransition( string $from, string $to ): bool;

	/**
	 * Obtém transições permitidas do estado atual.
	 *
	 * @param string $state Estado atual.
	 * @return array Estados permitidos.
	 */
	public static function getAllowedTransitions( string $state ): array;

	/**
	 * Verifica se documento pode ser assinado.
	 *
	 * @param string $state Estado atual.
	 * @return bool True se assinável.
	 */
	public static function isSignable( string $state ): bool;

	/**
	 * Verifica se documento pode ser editado.
	 *
	 * @param string $state Estado atual.
	 * @return bool True se editável.
	 */
	public static function isEditable( string $state ): bool;
}

/**
 * INTERFACE: SignatureSecurity
 * ============================
 *
 * Validação e auditoria de assinaturas.
 */
interface SignatureSecurityInterface {

	/**
	 * Valida assinatura antes de armazenar.
	 *
	 * @param array $signature Dados da assinatura.
	 * @param int   $post_id   ID do documento.
	 * @return array {
	 *     @type bool   $valid   Se é válida.
	 *     @type string $error   Mensagem de erro (se inválida).
	 *     @type array  $data    Dados sanitizados.
	 * }
	 */
	public static function validateSignature( array $signature, int $post_id ): array;

	/**
	 * Valida CPF.
	 *
	 * @param string $cpf CPF a validar.
	 * @return bool True se válido.
	 */
	public static function isValidCpf( string $cpf ): bool;

	/**
	 * Hash CPF para armazenamento (LGPD).
	 *
	 * @param string $cpf CPF limpo.
	 * @return string Hash do CPF.
	 */
	public static function hashCpf( string $cpf ): string;

	/**
	 * Cria registro de auditoria.
	 *
	 * @param int   $post_id   ID do documento.
	 * @param array $signature Dados da assinatura.
	 * @param int   $user_id   ID do usuário (se logado).
	 * @return array Registro de auditoria.
	 */
	public static function createAuditRecord( int $post_id, array $signature, int $user_id = 0 ): array;

	/**
	 * Verifica hash de assinatura.
	 *
	 * @param int    $signature_id ID da assinatura.
	 * @param string $hash         Hash a verificar.
	 * @return bool True se válido.
	 */
	public static function verifySignature( int $signature_id, string $hash ): bool;
}

/**
 * TABELAS DO BANCO
 * ================
 *
 * Schema das tabelas customizadas.
 */

/**
 * wp_apollo_documents (índice/cache)
 *
 * | Coluna      | Tipo         | Notas                    |
 * |-------------|--------------|--------------------------|
 * | id          | BIGINT       | PK, auto increment       |
 * | post_id     | BIGINT       | FK para wp_posts.ID      |
 * | group_id    | BIGINT       | FK para grupo            |
 * | doc_type    | VARCHAR(50)  | Tipo do documento        |
 * | status      | VARCHAR(50)  | Estado Apollo (cache)    |
 * | created_at  | DATETIME     | Data criação             |
 * | updated_at  | DATETIME     | Última atualização       |
 *
 * NOTA: Esta tabela é um ÍNDICE, não a fonte de verdade.
 * O CPT apollo_document é a fonte de verdade.
 */

/**
 * wp_apollo_document_signatures
 *
 * | Coluna          | Tipo          | Notas                       |
 * |-----------------|---------------|-----------------------------|
 * | id              | BIGINT        | PK, auto increment          |
 * | document_id     | BIGINT        | FK para wp_apollo_documents |
 * | post_id         | BIGINT        | FK para wp_posts.ID         |
 * | signature_id    | VARCHAR(64)   | UUID único                  |
 * | signer_user_id  | BIGINT        | FK para wp_users (nullable) |
 * | signer_cpf_hash | VARCHAR(64)   | Hash do CPF (LGPD)          |
 * | signer_name     | VARCHAR(255)  | Nome do signatário          |
 * | method          | VARCHAR(50)   | Método de assinatura        |
 * | doc_hash        | VARCHAR(64)   | Hash do documento           |
 * | pdf_hash        | VARCHAR(64)   | Hash do PDF                 |
 * | ip_address      | VARCHAR(45)   | IP do signatário            |
 * | signed_at       | DATETIME      | Data/hora da assinatura     |
 * | created_at      | DATETIME      | Data criação registro       |
 *
 * IMPORTANTE: Sempre use post_id para queries.
 * document_id mantido para compatibilidade.
 */

/**
 * HOOKS DISPONÍVEIS
 * =================
 *
 * Actions e filters para extensibilidade.
 */

// Actions
// -------
// apollo_document_created          - Após criar documento
// apollo_document_updated          - Após atualizar documento
// apollo_document_status_changed   - Após mudar status
// apollo_document_signed           - Após assinar
// apollo_signature_verified        - Após verificar assinatura

// Filters
// -------
// apollo_document_data             - Modificar dados antes de salvar
// apollo_signature_data            - Modificar assinatura antes de salvar
// apollo_status_transitions        - Modificar transições permitidas
// apollo_verification_url          - Modificar URL de verificação

/**
 * EXEMPLOS DE USO
 * ===============
 */

/*
// Criar documento
$post_id = DocumentsRepository::createDocument([
    'title'   => 'Contrato de Locação',
    'content' => 'Conteúdo do contrato...',
    'state'   => 'draft',
]);

// Transicionar para assinatura
DocumentsRepository::transitionStatus($post_id, 'signing');

// Armazenar assinatura
$sig_id = DocumentsRepository::storeSignature($post_id, [
    'signer_cpf'  => '123.456.789-00',
    'signer_name' => 'João Silva',
    'method'      => 'cpf',
    'ip_address'  => $_SERVER['REMOTE_ADDR'],
]);

// Obter URL de verificação
$url = SignatureSecurity::getVerificationUrl($sig_id);

// Verificar assinatura
$valid = SignatureSecurity::verifySignature($sig_id, $hash);
*/
