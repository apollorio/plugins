<?php

namespace Apollo\Application\Users;

/**
 * VerifyInstagram
 * Handles Instagram verification upload and status management
 */
class VerifyInstagram
{
    private const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB
    private const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    private const UPLOAD_DIR = 'apollo-verification-assets';
    
    /**
     * Handle verification image upload
     */
    public function handleUpload(int $user_id, array $files): array
    {
        try {
            // Rate limiting check
            $rate_check = $this->checkUploadRateLimit($user_id);
            if (!$rate_check['allowed']) {
                return [
                    'success' => false,
                    'message' => "Aguarde {$rate_check['wait_time']} segundos antes de enviar novamente"
                ];
            }
            
            // Validate user and verification status
            $validation = $this->validateUploadRequest($user_id);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'message' => $validation['message']
                ];
            }
            
            // Validate uploaded files
            $file_validation = $this->validateUploadedFiles($files);
            if (!$file_validation['valid']) {
                return [
                    'success' => false,
                    'message' => $file_validation['message'],
                    'errors' => $file_validation['errors']
                ];
            }
            
            // Process and store uploads
            $upload_result = $this->processUploads($user_id, $files);
            if (!$upload_result['success']) {
                return $upload_result;
            }
            
            // Update verification status
            $this->updateVerificationStatus($user_id, $upload_result['assets']);
            
            // Log upload event
            $this->logVerificationEvent($user_id, 'assets_uploaded', $upload_result);
            
            return [
                'success' => true,
                'message' => 'Verificação enviada com sucesso',
                'assets' => $upload_result['assets'],
                'status' => 'assets_submitted'
            ];
            
        } catch (\Exception $e) {
            error_log('VerifyInstagram upload error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Erro interno no upload. Tente novamente.'
            ];
        }
    }
    
    /**
     * Get verification status for user
     */
    public function getVerificationStatus(int $user_id): array
    {
        global $wpdb;
        
        $verification_table = $wpdb->prefix . 'apollo_verifications';
        
        $verification = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$verification_table} WHERE user_id = %d ORDER BY id DESC LIMIT 1",
            $user_id
        ), ARRAY_A);
        
        if (!$verification) {
            return [
                'status' => 'not_found',
                'message' => 'Verificação não encontrada'
            ];
        }
        
        // Parse assets if they exist
        $assets = [];
        if (!empty($verification['verify_assets'])) {
            $assets = json_decode($verification['verify_assets'], true) ?: [];
        }
        
        return [
            'status' => $verification['verify_status'],
            'instagram_username' => $verification['instagram_username'],
            'verify_token' => $verification['verify_token'],
            'submitted_at' => $verification['submitted_at'],
            'reviewed_at' => $verification['reviewed_at'],
            'rejection_reason' => $verification['rejection_reason'],
            'assets' => $assets,
            'can_upload' => $this->canUploadAssets($verification)
        ];
    }
    
    /**
     * Validate upload request
     */
    private function validateUploadRequest(int $user_id): array
    {
        // Check if user exists and is onboarded
        $user = get_user_by('ID', $user_id);
        if (!$user) {
            return [
                'valid' => false,
                'message' => 'Usuário não encontrado'
            ];
        }
        
        // Check if onboarding is completed
        $onboarded = get_user_meta($user_id, 'apollo_onboarded', true);
        if (!$onboarded) {
            return [
                'valid' => false,
                'message' => 'Complete o onboarding primeiro'
            ];
        }
        
        // Check verification record exists
        global $wpdb;
        $verification_table = $wpdb->prefix . 'apollo_verifications';
        
        $verification = $wpdb->get_row($wpdb->prepare(
            "SELECT verify_status FROM {$verification_table} WHERE user_id = %d ORDER BY id DESC LIMIT 1",
            $user_id
        ));
        
        if (!$verification) {
            return [
                'valid' => false,
                'message' => 'Registro de verificação não encontrado'
            ];
        }
        
        // Check if already verified
        if ($verification->verify_status === 'verified') {
            return [
                'valid' => false,
                'message' => 'Conta já verificada'
            ];
        }
        
        return ['valid' => true];
    }
    
    /**
     * Validate uploaded files
     */
    private function validateUploadedFiles(array $files): array
    {
        $errors = [];
        
        if (empty($files['verification_images'])) {
            return [
                'valid' => false,
                'message' => 'Nenhuma imagem foi enviada',
                'errors' => ['images' => 'Envie pelo menos uma imagem']
            ];
        }
        
        $uploaded_files = $files['verification_images'];
        
        // Normalize single file to array
        if (!isset($uploaded_files['name'][0])) {
            $uploaded_files = [
                'name' => [$uploaded_files['name']],
                'type' => [$uploaded_files['type']],
                'tmp_name' => [$uploaded_files['tmp_name']],
                'error' => [$uploaded_files['error']],
                'size' => [$uploaded_files['size']]
            ];
        }
        
        $file_count = count($uploaded_files['name']);
        
        // Check file count limits
        if ($file_count > 3) {
            $errors['count'] = 'Máximo 3 imagens permitidas';
        }
        
        // Check each file
        for ($i = 0; $i < $file_count; $i++) {
            $file_errors = $this->validateSingleFile([
                'name' => $uploaded_files['name'][$i],
                'type' => $uploaded_files['type'][$i],
                'tmp_name' => $uploaded_files['tmp_name'][$i],
                'error' => $uploaded_files['error'][$i],
                'size' => $uploaded_files['size'][$i]
            ], $i);
            
            if (!empty($file_errors)) {
                $errors["file_{$i}"] = $file_errors;
            }
        }
        
        return [
            'valid' => empty($errors),
            'message' => empty($errors) ? 'Arquivos válidos' : 'Erro na validação dos arquivos',
            'errors' => $errors
        ];
    }
    
    /**
     * Validate single file
     */
    private function validateSingleFile(array $file, int $index): array
    {
        $errors = [];
        
        // Check upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            switch ($file['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $errors[] = 'Arquivo muito grande';
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $errors[] = 'Upload incompleto';
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $errors[] = 'Nenhum arquivo enviado';
                    break;
                default:
                    $errors[] = 'Erro no upload';
            }
            return $errors;
        }
        
        // Check file size
        if ($file['size'] > self::MAX_FILE_SIZE) {
            $errors[] = 'Arquivo muito grande (máx 5MB)';
        }
        
        // Check file type
        $file_info = finfo_open(FILEINFO_MIME_TYPE);
        $detected_type = finfo_file($file_info, $file['tmp_name']);
        finfo_close($file_info);
        
        if (!in_array($detected_type, self::ALLOWED_TYPES)) {
            $errors[] = 'Tipo de arquivo não permitido (apenas JPG, PNG, GIF, WEBP)';
        }
        
        // Check if it's actually an image
        $image_info = getimagesize($file['tmp_name']);
        if ($image_info === false) {
            $errors[] = 'Arquivo não é uma imagem válida';
        }
        
        return $errors;
    }
    
    /**
     * Process and store uploads
     */
    private function processUploads(int $user_id, array $files): array
    {
        $uploaded_files = $files['verification_images'];
        
        // Normalize single file to array
        if (!isset($uploaded_files['name'][0])) {
            $uploaded_files = [
                'name' => [$uploaded_files['name']],
                'type' => [$uploaded_files['type']],
                'tmp_name' => [$uploaded_files['tmp_name']],
                'error' => [$uploaded_files['error']],
                'size' => [$uploaded_files['size']]
            ];
        }
        
        $file_count = count($uploaded_files['name']);
        $stored_assets = [];
        
        // Create upload directory
        $upload_dir = $this->createUploadDirectory($user_id);
        if (!$upload_dir['success']) {
            return $upload_dir;
        }
        
        // Process each file
        for ($i = 0; $i < $file_count; $i++) {
            $file = [
                'name' => $uploaded_files['name'][$i],
                'type' => $uploaded_files['type'][$i],
                'tmp_name' => $uploaded_files['tmp_name'][$i],
                'error' => $uploaded_files['error'][$i],
                'size' => $uploaded_files['size'][$i]
            ];
            
            $store_result = $this->storeFile($file, $upload_dir['path'], $user_id, $i);
            if ($store_result['success']) {
                $stored_assets[] = $store_result['asset'];
            } else {
                // Clean up previously stored files on error
                $this->cleanupStoredFiles($stored_assets);
                return [
                    'success' => false,
                    'message' => "Erro ao salvar arquivo {$file['name']}: {$store_result['message']}"
                ];
            }
        }
        
        return [
            'success' => true,
            'assets' => $stored_assets
        ];
    }
    
    /**
     * Create upload directory for user
     */
    private function createUploadDirectory(int $user_id): array
    {
        $wp_upload_dir = wp_upload_dir();
        $base_dir = $wp_upload_dir['basedir'] . '/' . self::UPLOAD_DIR;
        $user_dir = $base_dir . "/user_{$user_id}";
        
        // Create directories if they don't exist
        if (!file_exists($base_dir)) {
            if (!wp_mkdir_p($base_dir)) {
                return [
                    'success' => false,
                    'message' => 'Erro ao criar diretório de upload'
                ];
            }
        }
        
        if (!file_exists($user_dir)) {
            if (!wp_mkdir_p($user_dir)) {
                return [
                    'success' => false,
                    'message' => 'Erro ao criar diretório do usuário'
                ];
            }
        }
        
        // Add index.php for security
        $index_file = $user_dir . '/index.php';
        if (!file_exists($index_file)) {
            file_put_contents($index_file, '<?php // Silence is golden');
        }
        
        return [
            'success' => true,
            'path' => $user_dir,
            'url' => $wp_upload_dir['baseurl'] . '/' . self::UPLOAD_DIR . "/user_{$user_id}"
        ];
    }
    
    /**
     * Store individual file
     */
    private function storeFile(array $file, string $upload_dir, int $user_id, int $index): array
    {
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $verify_token = get_user_meta($user_id, 'apollo_verify_token', true);
        
        // Generate unique filename
        $filename = sprintf(
            'verification_%s_%d_%s.%s',
            $verify_token,
            $index + 1,
            uniqid(),
            $file_extension
        );
        
        $file_path = $upload_dir . '/' . $filename;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $file_path)) {
            return [
                'success' => false,
                'message' => 'Erro ao mover arquivo'
            ];
        }
        
        // Set secure permissions
        chmod($file_path, 0644);
        
        // Get file info
        $file_size = filesize($file_path);
        $image_info = getimagesize($file_path);
        
        return [
            'success' => true,
            'asset' => [
                'filename' => $filename,
                'original_name' => $file['name'],
                'file_path' => $file_path,
                'file_size' => $file_size,
                'mime_type' => $file['type'],
                'dimensions' => [
                    'width' => $image_info[0] ?? 0,
                    'height' => $image_info[1] ?? 0
                ],
                'uploaded_at' => current_time('mysql')
            ]
        ];
    }
    
    /**
     * Update verification status with assets
     */
    private function updateVerificationStatus(int $user_id, array $assets): void
    {
        global $wpdb;
        
        $verification_table = $wpdb->prefix . 'apollo_verifications';
        
        $wpdb->update(
            $verification_table,
            [
                'verify_status' => 'assets_submitted',
                'verify_assets' => json_encode($assets)
            ],
            ['user_id' => $user_id],
            ['%s', '%s'],
            ['%d']
        );
        
        // Update user meta
        update_user_meta($user_id, 'apollo_verify_status', 'assets_submitted');
    }
    
    /**
     * Check if user can upload assets
     */
    private function canUploadAssets(array $verification): bool
    {
        $allowed_statuses = [
            'awaiting_instagram_verify',
            'rejected'
        ];
        
        return in_array($verification['verify_status'], $allowed_statuses);
    }
    
    /**
     * Clean up stored files on error
     */
    private function cleanupStoredFiles(array $assets): void
    {
        foreach ($assets as $asset) {
            if (file_exists($asset['file_path'])) {
                unlink($asset['file_path']);
            }
        }
    }
    
    /**
     * Log verification event
     */
    private function logVerificationEvent(int $user_id, string $event, array $data): void
    {
        global $wpdb;
        
        $audit_table = $wpdb->prefix . 'apollo_audit_log';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '{$audit_table}'") != $audit_table) {
            return;
        }
        
        $wpdb->insert($audit_table, [
            'user_id' => $user_id,
            'action' => $event,
            'entity_type' => 'verification',
            'entity_id' => $user_id,
            'metadata' => json_encode([
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'ip_address' => $this->getClientIp(),
                'upload_data' => $data
            ]),
            'created_at' => current_time('mysql')
        ]);
    }
    
    /**
     * Get client IP address
     */
    private function getClientIp(): string
    {
        $ip_headers = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        
        foreach ($ip_headers as $header) {
            if (!empty($_SERVER[$header])) {
                return $_SERVER[$header];
            }
        }
        
        return 'unknown';
    }
    
    /**
     * Upload rate limiting (1 upload per 30s per user)
     */
    private function checkUploadRateLimit(int $user_id): array
    {
        $cache_key = "apollo_verification_upload_rate_limit_{$user_id}";
        $last_upload = wp_cache_get($cache_key);
        
        if ($last_upload && (time() - $last_upload) < 30) {
            return [
                'allowed' => false,
                'wait_time' => 30 - (time() - $last_upload)
            ];
        }
        
        // Set rate limit
        wp_cache_set($cache_key, time(), '', 30);
        
        return ['allowed' => true];
    }
    
    /**
     * Delete verification assets (for re-upload)
     */
    public function deleteAssets(int $user_id): array
    {
        try {
            // Get current verification
            $status = $this->getVerificationStatus($user_id);
            
            if ($status['status'] === 'verified') {
                return [
                    'success' => false,
                    'message' => 'Não é possível deletar assets de conta verificada'
                ];
            }
            
            // Delete files
            if (!empty($status['assets'])) {
                foreach ($status['assets'] as $asset) {
                    if (file_exists($asset['file_path'])) {
                        unlink($asset['file_path']);
                    }
                }
            }
            
            // Update database
            global $wpdb;
            $verification_table = $wpdb->prefix . 'apollo_verifications';
            
            $wpdb->update(
                $verification_table,
                [
                    'verify_status' => 'awaiting_instagram_verify',
                    'verify_assets' => null
                ],
                ['user_id' => $user_id],
                ['%s', '%s'],
                ['%d']
            );
            
            update_user_meta($user_id, 'apollo_verify_status', 'awaiting_instagram_verify');
            
            return [
                'success' => true,
                'message' => 'Assets deletados com sucesso'
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao deletar assets'
            ];
        }
    }
}