<?php

// phpcs:ignoreFile.
/**
 * Script to add type hints to Apollo Core functions
 *
 * Usage: php scripts/add-type-hints.php
 */

$plugin_dir = dirname(__DIR__);

// Define replacements for each file.
$replacements = [
    // includes/memberships.php
    'includes/memberships.php' => [
        [
            'search'  => 'function apollo_get_default_memberships() {',
            'replace' => 'function apollo_get_default_memberships(): array {',
        ],
        [
            'search'  => 'function apollo_get_memberships() {',
            'replace' => 'function apollo_get_memberships(): array {',
        ],
        [
            'search'  => 'function apollo_save_memberships( $memberships ) {',
            'replace' => 'function apollo_save_memberships( array $memberships ): bool {',
        ],
        [
            'search'  => 'function apollo_get_user_membership( $user_id ) {',
            'replace' => 'function apollo_get_user_membership( int $user_id ): string {',
        ],
        [
            'search'  => 'function apollo_set_user_membership( $user_id, $membership_slug, $actor_id = null ) {',
            'replace' => 'function apollo_set_user_membership( int $user_id, string $membership_slug, ?int $actor_id = null ): bool {',
        ],
        [
            'search'  => 'function apollo_migrate_memberships() {',
            'replace' => 'function apollo_migrate_memberships(): void {',
        ],
    ],

    // includes/forms/schema-manager.php
    'includes/forms/schema-manager.php' => [
        [
            'search'  => 'function apollo_get_default_form_schemas() {',
            'replace' => 'function apollo_get_default_form_schemas(): array {',
        ],
        [
            'search'  => 'function apollo_get_form_schema( $form_type ) {',
            'replace' => 'function apollo_get_form_schema( string $form_type ): array {',
        ],
        [
            'search'  => 'function apollo_save_form_schema( $form_type, $schema ) {',
            'replace' => 'function apollo_save_form_schema( string $form_type, array $schema ): bool {',
        ],
        [
            'search'  => 'function apollo_migrate_form_schema() {',
            'replace' => 'function apollo_migrate_form_schema(): void {',
        ],
        [
            'search'  => 'function apollo_is_instagram_id_unique( $instagram_id, $exclude_user_id = 0 ) {',
            'replace' => 'function apollo_is_instagram_id_unique( string $instagram_id, int $exclude_user_id = 0 ): bool {',
        ],
    ],

    // includes/quiz/schema-manager.php
    'includes/quiz/schema-manager.php' => [
        [
            'search'  => 'function apollo_get_default_quiz_schemas() {',
            'replace' => 'function apollo_get_default_quiz_schemas(): array {',
        ],
        [
            'search'  => 'function apollo_get_quiz_schema( $form_type ) {',
            'replace' => 'function apollo_get_quiz_schema( string $form_type ): array {',
        ],
        [
            'search'  => 'function apollo_save_quiz_question( $form_type, $question_data, $question_id = null ) {',
            'replace' => 'function apollo_save_quiz_question( string $form_type, array $question_data, ?string $question_id = null ): string {',
        ],
        [
            'search'  => 'function apollo_delete_quiz_question( $form_type, $question_id ) {',
            'replace' => 'function apollo_delete_quiz_question( string $form_type, string $question_id ): bool {',
        ],
        [
            'search'  => 'function apollo_get_quiz_question( $form_type, $question_id ) {',
            'replace' => 'function apollo_get_quiz_question( string $form_type, string $question_id ): array {',
        ],
        [
            'search'  => 'function apollo_set_quiz_enabled( $form_type, $enabled ) {',
            'replace' => 'function apollo_set_quiz_enabled( string $form_type, bool $enabled ): bool {',
        ],
        [
            'search'  => 'function apollo_is_quiz_enabled( $form_type ) {',
            'replace' => 'function apollo_is_quiz_enabled( string $form_type ): bool {',
        ],
        [
            'search'  => 'function apollo_get_active_quiz_questions( $form_type ) {',
            'replace' => 'function apollo_get_active_quiz_questions( string $form_type ): array {',
        ],
        [
            'search'  => 'function apollo_migrate_quiz_schema() {',
            'replace' => 'function apollo_migrate_quiz_schema(): void {',
        ],
        [
            'search'  => 'function apollo_get_default_insta_info() {',
            'replace' => 'function apollo_get_default_insta_info(): array {',
        ],
        [
            'search'  => 'function apollo_get_insta_info( $form_type ) {',
            'replace' => 'function apollo_get_insta_info( string $form_type ): array {',
        ],
        [
            'search'  => 'function apollo_save_insta_info( $form_type, $data ) {',
            'replace' => 'function apollo_save_insta_info( string $form_type, array $data ): bool {',
        ],
    ],

    // includestentantivas.php
    'includestentantivas.php' => [
        [
            'search'  => 'function apollo_create_quiz_attempts_table() {',
            'replace' => 'function apollo_create_quiz_attempts_table(): void {',
        ],
        [
            'search'  => 'function apollo_record_quiz_attempt( $user_id, $question_id, $answers, $passed, $attempt_number, $form_type = \'new_user\' ) {',
            'replace' => 'function apollo_record_quiz_attempt( int $user_id, string $question_id, array $answers, bool $passed, int $attempt_number, string $form_type = \'new_user\' ): bool {',
        ],
        [
            'search'  => 'function apollo_get_user_quiz_attempts( $user_id, $question_id = null, $form_type = \'new_user\' ) {',
            'replace' => 'function apollo_get_user_quiz_attempts( int $user_id, ?string $question_id = null, string $form_type = \'new_user\' ): array {',
        ],
        [
            'search'  => 'function apollo_get_user_quiz_status( $user_id, $form_type = \'new_user\' ) {',
            'replace' => 'function apollo_get_user_quiz_status( int $user_id, string $form_type = \'new_user\' ): array {',
        ],
        [
            'search'  => 'function apollo_evaluate_quiz_answers( $form_type, $user_answers ) {',
            'replace' => 'function apollo_evaluate_quiz_answers( string $form_type, array $user_answers ): array {',
        ],
        [
            'search'  => 'function apollo_check_quiz_rate_limit( $user_id = 0, $ip_address = \'\' ) {',
            'replace' => 'function apollo_check_quiz_rate_limit( int $user_id = 0, string $ip_address = \'\' ): bool {',
        ],
    ],

    // includes/quiz/quiz-defaults.php
    'includes/quiz/quiz-defaults.php' => [
        [
            'search'  => 'function apollo_get_default_quiz_questions() {',
            'replace' => 'function apollo_get_default_quiz_questions(): array {',
        ],
        [
            'search'  => 'function apollo_seed_default_quiz_questions() {',
            'replace' => 'function apollo_seed_default_quiz_questions(): void {',
        ],
    ],

    // includes/roles.php
    'includes/roles.php' => [
        [
            'search'  => 'function apollo_create_custom_roles() {',
            'replace' => 'function apollo_create_custom_roles(): void {',
        ],
        [
            'search'  => 'function apollo_remove_custom_roles() {',
            'replace' => 'function apollo_remove_custom_roles(): void {',
        ],
    ],

    // includes/settings-defaults.php
    'includes/settings-defaults.php' => [
        [
            'search'  => 'function apollo_get_default_mod_settings() {',
            'replace' => 'function apollo_get_default_mod_settings(): array {',
        ],
        [
            'search'  => 'function apollo_get_mod_settings() {',
            'replace' => 'function apollo_get_mod_settings(): array {',
        ],
    ],
];

$files_updated = 0;
$files_failed  = 0;

foreach ($replacements as $relative_path => $rules) {
    $filepath = $plugin_dir . '/' . $relative_path;

    if (! file_exists($filepath)) {
        echo "⚠️  File not found: {$relative_path}\n";
        ++$files_failed;

        continue;
    }

    $content          = file_get_contents($filepath);
    $original_content = $content;

    foreach ($rules as $rule) {
        $count   = 0;
        $content = str_replace($rule['search'], $rule['replace'], $content, $count);

        if ($count > 0) {
            // Success indicator handled after all replacements.
        }
    }

    if ($content !== $original_content) {
        file_put_contents($filepath, $content);
        echo "✅ Updated: {$relative_path}\n";
        ++$files_updated;
    } else {
        echo "⏭️  No changes: {$relative_path}\n";
        ++$files_failed;
    }
}//end foreach

echo "\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📊 SUMMARY\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo '✅ Files updated: ' . $files_updated . "\n";
echo '⏭️  Files failed: ' . $files_failed . "\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
