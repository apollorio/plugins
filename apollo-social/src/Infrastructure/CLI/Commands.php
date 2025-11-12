<?php

namespace Apollo\Infrastructure\CLI;

use Apollo\Infrastructure\Database\Schema;
use Apollo\Infrastructure\Security\Caps;
use Apollo\Infrastructure\Workflows\ContentWorkflow;
use Apollo\Application\Users\VerifyInstagram;
use Apollo\Infrastructure\Adapters\WPAdvertsAdapter;

/**
 * Apollo CLI Commands
 * 
 * WP-CLI commands for Apollo Social management and maintenance.
 */
class Commands
{
    /**
     * Register CLI commands
     */
    public function register(): void
    {
        if (defined('WP_CLI') && WP_CLI) {
            \WP_CLI::add_command('apollo', $this);
            \WP_CLI::add_command('apollo-social verify', [$this, 'verify']);
            \WP_CLI::add_command('apollo-social adverts', [$this, 'adverts']);
        }
    }

    /**
     * Install Apollo Social database schema
     * 
     * ## EXAMPLES
     * 
     *     wp apollo install
     * 
     * @when after_wp_load
     */
    public function install($args, $assoc_args): void
    {
        $schema = new Schema();
        
        \WP_CLI::log('Installing Apollo Social database schema...');
        
        try {
            $schema->install();
            $schema->updateGroupsTable();
            $schema->updateAdsTable();
            $schema->createVerificationTokensTable();
            
            \WP_CLI::success('Apollo Social database schema installed successfully!');
            
            // Show installation status
            $status = $schema->getInstallationStatus();
            \WP_CLI::log('Installation Status:');
            
            foreach ($status as $table => $exists) {
                if ($table === 'schema_version' || $table === 'needs_update') continue;
                
                $status_icon = $exists ? '✅' : '❌';
                \WP_CLI::log("  {$status_icon} {$table}");
            }
            
            \WP_CLI::log("Schema Version: {$status['schema_version']}");
            
        } catch (Exception $e) {
            \WP_CLI::error('Failed to install database schema: ' . $e->getMessage());
        }
    }

    /**
     * Setup Apollo capabilities and permissions
     * 
     * ## EXAMPLES
     * 
     *     wp apollo setup-permissions
     * 
     * @when after_wp_load
     */
    public function setupPermissions($args, $assoc_args): void
    {
        $caps = new Caps();
        
        \WP_CLI::log('Setting up Apollo Social capabilities and permissions...');
        
        try {
            $caps->init();
            $caps->registerCapabilities();
            $caps->assignCapabilitiesToRoles();
            
            \WP_CLI::success('Apollo Social permissions configured successfully!');
            
            // Show permission summary
            \WP_CLI::log('Permission Summary:');
            \WP_CLI::log('  Administrator: Full access to all features');
            \WP_CLI::log('  Editor: Can publish directly, moderate content');
            \WP_CLI::log('  Author: Events publish directly, other content needs approval');
            \WP_CLI::log('  Contributor: Creates drafts only');
            \WP_CLI::log('  Subscriber: User posts and classifieds (contract & published)');
            
        } catch (Exception $e) {
            \WP_CLI::error('Failed to setup permissions: ' . $e->getMessage());
        }
    }

    /**
     * Migration command for Apollo data
     * 
     * ## OPTIONS
     * 
     * [--dry-run]
     * : Show what would be migrated without making changes
     * 
     * [--force]
     * : Force migration even if already migrated
     * 
     * ## EXAMPLES
     * 
     *     wp apollo migrate
     *     wp apollo migrate --dry-run
     *     wp apollo migrate --force
     * 
     * @when after_wp_load
     */
    public function migrate($args, $assoc_args): void
    {
        $dry_run = isset($assoc_args['dry-run']);
        $force = isset($assoc_args['force']);
        
        $schema = new Schema();
        
        if ($dry_run) {
            \WP_CLI::log('=== DRY RUN MODE ===');
        }
        
        \WP_CLI::log('Checking Apollo Social migration status...');
        
        if (!$schema->needsUpdate() && !$force) {
            \WP_CLI::success('Apollo Social is already up to date!');
            return;
        }
        
        \WP_CLI::log('Migration needed. Current version: ' . $schema->getSchemaVersion());
        
        if (!$dry_run) {
            try {
                $schema->migrate();
                \WP_CLI::success('Apollo Social migrated successfully!');
            } catch (Exception $e) {
                \WP_CLI::error('Migration failed: ' . $e->getMessage());
            }
        } else {
            \WP_CLI::log('Would migrate to latest version');
        }
    }

    /**
     * Show Apollo Social statistics
     * 
     * ## EXAMPLES
     * 
     *     wp apollo stats
     * 
     * @when after_wp_load
     */
    public function stats($args, $assoc_args): void
    {
        $schema = new Schema();
        
        \WP_CLI::log('Apollo Social Statistics:');
        \WP_CLI::log('========================');
        
        try {
            $stats = $schema->getStatistics();
            
            \WP_CLI::log('📊 Content Workflow:');
            \WP_CLI::log("  • Total state transitions: {$stats['workflow_transitions']}");
            \WP_CLI::log("  • Pending moderation: {$stats['pending_moderation']}");
            
            \WP_CLI::log('📈 Analytics:');
            \WP_CLI::log("  • Total events tracked: {$stats['total_events']}");
            \WP_CLI::log("  • Events today: {$stats['events_today']}");
            
            \WP_CLI::log('✍️ Signatures:');
            \WP_CLI::log("  • Total requests: {$stats['signature_requests']}");
            \WP_CLI::log("  • Pending signatures: {$stats['pending_signatures']}");
            
            \WP_CLI::log('🎯 Onboarding:');
            \WP_CLI::log("  • Users in onboarding: {$stats['users_in_onboarding']}");
            \WP_CLI::log("  • Completed onboarding: {$stats['completed_onboarding']}");
            
        } catch (Exception $e) {
            \WP_CLI::error('Failed to get statistics: ' . $e->getMessage());
        }
    }

    /**
     * Test content workflow transitions
     * 
     * ## OPTIONS
     * 
     * <content_type>
     * : Content type to test (group, event, ad)
     * 
     * [--user-role=<role>]
     * : Test as specific user role
     * : default: subscriber
     * 
     * ## EXAMPLES
     * 
     *     wp apollo test-workflow group
     *     wp apollo test-workflow event --user-role=author
     * 
     * @when after_wp_load
     */
    public function testWorkflow($args, $assoc_args): void
    {
        $content_type = $args[0] ?? 'group';
        $user_role = $assoc_args['user-role'] ?? 'subscriber';
        
        if (!in_array($content_type, ['group', 'event', 'ad'])) {
            \WP_CLI::error('Content type must be: group, event, or ad');
            return;
        }
        
        \WP_CLI::log("Testing workflow for '{$content_type}' as '{$user_role}':");
        \WP_CLI::log('=================================================');
        
        $workflow = new ContentWorkflow();
        
        // Test initial state logic
        $test_data = [];
        if ($content_type === 'group') {
            $test_data = ['type' => 'comunidade']; // Test grupo that requires approval
        }
        
        $initial_state = $this->simulateUserRole($user_role, function() use ($workflow, $content_type, $test_data) {
            return $workflow->getInitialState($content_type, $test_data);
        });
        
        $status_display = $workflow->getStatusDisplay($initial_state);
        
        \WP_CLI::log("Initial state: {$status_display['icon']} {$status_display['label']}");
        \WP_CLI::log("Description: {$status_display['description']}");
        \WP_CLI::log("Public visibility: " . ($status_display['public'] ? 'Yes' : 'No'));
        
        // Test available transitions
        $transitions = $this->simulateUserRole($user_role, function() use ($workflow, $initial_state, $content_type) {
            return $workflow->getAvailableTransitions($initial_state, $content_type);
        });
        
        if (!empty($transitions)) {
            \WP_CLI::log("\nAvailable transitions:");
            foreach ($transitions as $transition) {
                $display = $transition['display'];
                \WP_CLI::log("  → {$display['icon']} {$display['label']}");
            }
        } else {
            \WP_CLI::log("\nNo transitions available for this role.");
        }
    }

    /**
     * Reset Apollo Social (DANGEROUS)
     * 
     * ## OPTIONS
     * 
     * [--confirm]
     * : Confirm the reset operation
     * 
     * ## EXAMPLES
     * 
     *     wp apollo reset --confirm
     * 
     * @when after_wp_load
     */
    public function reset($args, $assoc_args): void
    {
        if (!isset($assoc_args['confirm'])) {
            \WP_CLI::error('This operation will delete ALL Apollo Social data. Use --confirm to proceed.');
            return;
        }
        
        \WP_CLI::confirm('Are you ABSOLUTELY sure you want to reset Apollo Social? This cannot be undone.');
        
        $schema = new Schema();
        $caps = new Caps();
        
        \WP_CLI::log('Resetting Apollo Social...');
        
        try {
            // Remove capabilities
            $caps->removeAllCapabilities();
            \WP_CLI::log('✅ Capabilities removed');
            
            // Drop database tables
            $schema->uninstall();
            \WP_CLI::log('✅ Database tables dropped');
            
            \WP_CLI::success('Apollo Social has been completely reset!');
            
        } catch (Exception $e) {
            \WP_CLI::error('Reset failed: ' . $e->getMessage());
        }
    }

    /**
     * Seed test data for Apollo Social
     * 
     * ## OPTIONS
     * 
     * [--users]
     * : Create test users with different roles
     * 
     * [--seasons]
     * : Create test seasons
     * 
     * [--content]
     * : Create test content
     * 
     * ## EXAMPLES
     * 
     *     wp apollo seed --users --seasons --content
     *     wp apollo seed --users
     * 
     * @when after_wp_load
     */
    public function seed($args, $assoc_args): void
    {
        if (isset($assoc_args['users'])) {
            $this->seedUsers();
        }
        
        if (isset($assoc_args['seasons'])) {
            $this->seedSeasons();
        }
        
        if (isset($assoc_args['content'])) {
            $this->seedContent();
        }
        
        if (empty($assoc_args)) {
            \WP_CLI::log('Use --users, --seasons, or --content flags');
        }
    }

    /**
     * Create test content for workflow testing
     * 
     * ## OPTIONS
     * 
     * <type>
     * : Content type (post, ad, event, group)
     * 
     * --user=<username>
     * : Username to create content as
     * 
     * --title=<title>
     * : Content title
     * 
     * [--group-type=<type>]
     * : Group type (nucleo, comunidade, post, discussion)
     * 
     * [--season=<slug>]
     * : Season slug for classifieds
     * 
     * ## EXAMPLES
     * 
     *     wp apollo create post --user=subscriber_test --title="Post teste"
     *     wp apollo create group --user=subscriber_test --title="Núcleo teste" --group-type=nucleo
     * 
     * @when after_wp_load
     */
    public function create($args, $assoc_args): void
    {
        $type = $args[0] ?? 'post';
        $username = $assoc_args['user'] ?? '';
        $title = $assoc_args['title'] ?? "Teste {$type}";
        
        if (empty($username)) {
            \WP_CLI::error('--user parameter is required');
            return;
        }
        
        $user = get_user_by('login', $username);
        if (!$user) {
            \WP_CLI::error("User '{$username}' not found");
            return;
        }
        
        // Set current user for testing
        wp_set_current_user($user->ID);
        
        $workflow = new ContentWorkflow();
        
        switch ($type) {
            case 'post':
                $result = $this->createTestPost($title, $workflow);
                break;
            case 'ad':
                $season = $assoc_args['season'] ?? 'verao-2026';
                $result = $this->createTestAd($title, $season, $workflow);
                break;
            case 'event':
                $result = $this->createTestEvent($title, $workflow);
                break;
            case 'group':
                $group_type = $assoc_args['group-type'] ?? 'nucleo';
                $result = $this->createTestGroup($title, $group_type, $workflow);
                break;
            default:
                \WP_CLI::error("Invalid content type: {$type}");
                return;
        }
        
        if ($result['success']) {
            \WP_CLI::success("Created {$type}: {$title} (ID: {$result['id']}, Status: {$result['status']})");
        } else {
            \WP_CLI::error("Failed to create {$type}: {$result['message']}");
        }
        
        // Reset current user
        wp_set_current_user(0);
    }

    /**
     * Manage group approval/rejection
     * 
     * ## SUBCOMMANDS
     * 
     * approve <id>
     * : Approve a group
     * 
     * reject <id> --reason=<reason>
     * : Reject a group with reason
     * 
     * list [--status=<status>]
     * : List groups by status
     * 
     * ## EXAMPLES
     * 
     *     wp apollo groups approve 123
     *     wp apollo groups reject 123 --reason="Dados incompletos"
     *     wp apollo groups list --status=pending_review
     * 
     * @when after_wp_load
     */
    public function groups($args, $assoc_args): void
    {
        $action = $args[0] ?? 'list';
        
        switch ($action) {
            case 'approve':
                $this->approveGroup($args, $assoc_args);
                break;
            case 'reject':
                $this->rejectGroup($args, $assoc_args);
                break;
            case 'list':
                $this->listGroups($assoc_args);
                break;
            default:
                \WP_CLI::error("Invalid action: {$action}");
        }
    }

    /**
     * Show workflow status map
     * 
     * ## EXAMPLES
     * 
     *     wp apollo status-map
     * 
     * @when after_wp_load
     */
    public function statusMap($args, $assoc_args): void
    {
        \WP_CLI::log('Apollo Social - Workflow Status Map');
        \WP_CLI::log('=====================================');
        
        $workflow = new ContentWorkflow();
        
        // Test matrix
        $roles = ['subscriber', 'contributor', 'author', 'editor', 'administrator'];
        $content_types = [
            'post' => ['type' => 'post'],
            'classified' => [],
            'event' => [],
            'nucleo' => ['type' => 'nucleo'],
            'comunidade' => ['type' => 'comunidade']
        ];
        
        \WP_CLI::log('Role\t\tPost\tClassified\tEvent\tNúcleo\tComunidade');
        \WP_CLI::log('----------------------------------------------------------------');
        
        foreach ($roles as $role) {
            $line = ucfirst($role) . str_repeat(' ', 12 - strlen($role));
            
            foreach ($content_types as $name => $data) {
                $content_type = in_array($name, ['post', 'nucleo', 'comunidade']) ? 'group' : 
                               ($name === 'classified' ? 'ad' : $name);
                
                $status = $this->simulateUserRole($role, function() use ($workflow, $content_type, $data) {
                    return $workflow->getInitialState($content_type, $data);
                });
                
                $short_status = $this->getShortStatus($status);
                $line .= "\t{$short_status}";
            }
            
            \WP_CLI::log($line);
        }
        
        \WP_CLI::log('');
        \WP_CLI::log('Legend:');
        \WP_CLI::log('  PUB = published (direct)');
        \WP_CLI::log('  PND = pending_review (needs approval)');
        \WP_CLI::log('  DRF = draft (needs editing)');
    }

    /**
     * Run workflow test matrix
     * 
     * ## EXAMPLES
     * 
     *     wp apollo test-matrix
     * 
     * @when after_wp_load
     */
    public function testMatrix($args, $assoc_args): void
    {
        \WP_CLI::log('Running Apollo Social Workflow Test Matrix...');
        \WP_CLI::log('===========================================');
        
        $tests = [
            ['role' => 'subscriber', 'type' => 'group', 'data' => ['type' => 'post'], 'expected' => 'published'],
            ['role' => 'subscriber', 'type' => 'ad', 'data' => [], 'expected' => 'published'],
            ['role' => 'subscriber', 'type' => 'event', 'data' => [], 'expected' => 'pending_review'],
            ['role' => 'subscriber', 'type' => 'group', 'data' => ['type' => 'nucleo'], 'expected' => 'pending_review'],
            
            ['role' => 'contributor', 'type' => 'group', 'data' => ['type' => 'post'], 'expected' => 'draft'],
            ['role' => 'contributor', 'type' => 'ad', 'data' => [], 'expected' => 'draft'],
            ['role' => 'contributor', 'type' => 'event', 'data' => [], 'expected' => 'draft'],
            
            ['role' => 'author', 'type' => 'group', 'data' => ['type' => 'post'], 'expected' => 'pending_review'],
            ['role' => 'author', 'type' => 'ad', 'data' => [], 'expected' => 'pending_review'],
            ['role' => 'author', 'type' => 'event', 'data' => [], 'expected' => 'published'],
            
            ['role' => 'editor', 'type' => 'group', 'data' => ['type' => 'post'], 'expected' => 'published'],
            ['role' => 'editor', 'type' => 'ad', 'data' => [], 'expected' => 'published'],
            ['role' => 'editor', 'type' => 'event', 'data' => [], 'expected' => 'published'],
        ];
        
        $passed = 0;
        $failed = 0;
        
        foreach ($tests as $test) {
            $actual = $this->simulateUserRole($test['role'], function() use ($test) {
                $workflow = new ContentWorkflow();
                return $workflow->getInitialState($test['type'], $test['data']);
            });
            
            $content_desc = $test['type'];
            if (!empty($test['data']['type'])) {
                $content_desc .= " ({$test['data']['type']})";
            }
            
            if ($actual === $test['expected']) {
                \WP_CLI::log("✅ {$test['role']} → {$content_desc} → {$actual}");
                $passed++;
            } else {
                \WP_CLI::log("❌ {$test['role']} → {$content_desc} → Expected: {$test['expected']}, Got: {$actual}");
                $failed++;
            }
        }
        
        \WP_CLI::log('');
        \WP_CLI::log("Results: {$passed} passed, {$failed} failed");
        
        if ($failed === 0) {
            \WP_CLI::success('All workflow tests passed! 🎉');
        } else {
            \WP_CLI::error("Some tests failed. Check workflow configuration.");
        }
    }

    /**
     * Simulate user role for testing
     */
    private function simulateUserRole(string $role, callable $callback)
    {
        // Create temporary user with specified role
        $user_id = wp_create_user('test_' . $role, 'password', 'test@example.com');
        $user = new \WP_User($user_id);
        $user->set_role($role);
        
        // Set as current user
        wp_set_current_user($user_id);
        
        try {
            $result = $callback();
        } finally {
            // Clean up
            wp_delete_user($user_id);
            wp_set_current_user(0);
        }
        
        return $result;
    }

    /**
     * Create test users with different roles
     */
    private function seedUsers(): void
    {
        \WP_CLI::log('Creating test users...');
        
        $roles = ['subscriber', 'contributor', 'author', 'editor'];
        
        foreach ($roles as $role) {
            $username = "{$role}_test";
            $email = "{$role}@apollo-test.com";
            
            // Check if user already exists
            if (get_user_by('login', $username)) {
                \WP_CLI::log("  ⚠️  User {$username} already exists");
                continue;
            }
            
            $user_id = wp_create_user($username, 'apollo123', $email);
            
            if (is_wp_error($user_id)) {
                \WP_CLI::log("  ❌ Failed to create {$username}: " . $user_id->get_error_message());
                continue;
            }
            
            $user = new \WP_User($user_id);
            $user->set_role($role);
            
            \WP_CLI::log("  ✅ Created {$username} ({$role})");
        }
    }

    /**
     * Create test seasons
     */
    private function seedSeasons(): void
    {
        \WP_CLI::log('Creating test seasons...');
        
        global $wpdb;
        
        $seasons = [
            [
                'slug' => 'verao-2026',
                'name' => 'Verão 2026',
                'start_date' => '2025-12-01',
                'end_date' => '2026-03-15',
                'status' => 'active'
            ],
            [
                'slug' => 'inverno-2025',
                'name' => 'Inverno 2025',
                'start_date' => '2025-06-01',
                'end_date' => '2025-09-15',
                'status' => 'archived'
            ]
        ];
        
        foreach ($seasons as $season) {
            // Check if season exists
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}apollo_seasons WHERE slug = %s",
                $season['slug']
            ));
            
            if ($exists) {
                \WP_CLI::log("  ⚠️  Season {$season['slug']} already exists");
                continue;
            }
            
            $result = $wpdb->insert(
                $wpdb->prefix . 'apollo_seasons',
                $season,
                ['%s', '%s', '%s', '%s', '%s']
            );
            
            if ($result) {
                \WP_CLI::log("  ✅ Created season {$season['slug']}");
            } else {
                \WP_CLI::log("  ❌ Failed to create season {$season['slug']}");
            }
        }
    }

    /**
     * Create test content
     */
    private function seedContent(): void
    {
        \WP_CLI::log('Creating test content...');
        
        // Create test content for different scenarios
        $content_items = [
            ['type' => 'post', 'user' => 'subscriber_test', 'title' => 'Post Social do Subscriber'],
            ['type' => 'ad', 'user' => 'subscriber_test', 'title' => 'Mesa de Som - Venda'],
            ['type' => 'event', 'user' => 'author_test', 'title' => 'Workshop de Música'],
            ['type' => 'group', 'user' => 'subscriber_test', 'title' => 'Núcleo de Teste', 'group_type' => 'nucleo']
        ];
        
        foreach ($content_items as $item) {
            try {
                // Set user context
                $user = get_user_by('login', $item['user']);
                if (!$user) {
                    \WP_CLI::log("  ❌ User {$item['user']} not found");
                    continue;
                }
                
                wp_set_current_user($user->ID);
                
                // Create content based on type
                $result = match($item['type']) {
                    'post' => $this->createTestPost($item['title'], new ContentWorkflow()),
                    'ad' => $this->createTestAd($item['title'], 'verao-2026', new ContentWorkflow()),
                    'event' => $this->createTestEvent($item['title'], new ContentWorkflow()),
                    'group' => $this->createTestGroup($item['title'], $item['group_type'] ?? 'post', new ContentWorkflow()),
                    default => ['success' => false, 'message' => 'Unknown type']
                };
                
                if ($result['success']) {
                    \WP_CLI::log("  ✅ Created {$item['type']}: {$item['title']} (Status: {$result['status']})");
                } else {
                    \WP_CLI::log("  ❌ Failed to create {$item['type']}: {$result['message']}");
                }
                
            } catch (Exception $e) {
                \WP_CLI::log("  ❌ Error creating {$item['type']}: " . $e->getMessage());
            } finally {
                wp_set_current_user(0);
            }
        }
    }

    /**
     * Create test post
     */
    private function createTestPost(string $title, ContentWorkflow $workflow): array
    {
        $initial_state = $workflow->getInitialState('group', ['type' => 'post']);
        
        global $wpdb;
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'apollo_groups',
            [
                'title' => $title,
                'description' => 'Post de teste criado automaticamente',
                'type' => 'post',
                'visibility' => 'public',
                'status' => $initial_state,
                'author_id' => get_current_user_id(),
                'created_at' => current_time('mysql')
            ],
            ['%s', '%s', '%s', '%s', '%s', '%d', '%s']
        );
        
        if ($result) {
            return [
                'success' => true,
                'id' => $wpdb->insert_id,
                'status' => $initial_state
            ];
        }
        
        return ['success' => false, 'message' => 'Database insert failed'];
    }

    /**
     * Create test ad
     */
    private function createTestAd(string $title, string $season, ContentWorkflow $workflow): array
    {
        $initial_state = $workflow->getInitialState('ad', []);
        
        global $wpdb;
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'apollo_ads',
            [
                'title' => $title,
                'description' => 'Anúncio de teste criado automaticamente',
                'category' => 'equipamentos',
                'price' => 500.00,
                'season_slug' => $season,
                'status' => $initial_state,
                'author_id' => get_current_user_id(),
                'created_at' => current_time('mysql')
            ],
            ['%s', '%s', '%s', '%f', '%s', '%s', '%d', '%s']
        );
        
        if ($result) {
            return [
                'success' => true,
                'id' => $wpdb->insert_id,
                'status' => $initial_state
            ];
        }
        
        return ['success' => false, 'message' => 'Database insert failed'];
    }

    /**
     * Create test event
     */
    private function createTestEvent(string $title, ContentWorkflow $workflow): array
    {
        $initial_state = $workflow->getInitialState('event', []);
        
        $post_data = [
            'post_title' => $title,
            'post_content' => 'Evento de teste criado automaticamente',
            'post_type' => 'eva_event',
            'post_status' => $initial_state,
            'post_author' => get_current_user_id()
        ];
        
        $post_id = wp_insert_post($post_data);
        
        if ($post_id && !is_wp_error($post_id)) {
            return [
                'success' => true,
                'id' => $post_id,
                'status' => $initial_state
            ];
        }
        
        return ['success' => false, 'message' => 'Failed to create post'];
    }

    /**
     * Create test group
     */
    private function createTestGroup(string $title, string $group_type, ContentWorkflow $workflow): array
    {
        $initial_state = $workflow->getInitialState('group', ['type' => $group_type]);
        
        global $wpdb;
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'apollo_groups',
            [
                'title' => $title,
                'description' => "Grupo de teste do tipo {$group_type}",
                'type' => $group_type,
                'visibility' => 'public',
                'status' => $initial_state,
                'author_id' => get_current_user_id(),
                'created_at' => current_time('mysql')
            ],
            ['%s', '%s', '%s', '%s', '%s', '%d', '%s']
        );
        
        if ($result) {
            return [
                'success' => true,
                'id' => $wpdb->insert_id,
                'status' => $initial_state
            ];
        }
        
        return ['success' => false, 'message' => 'Database insert failed'];
    }

    /**
     * Approve group
     */
    private function approveGroup(array $args, array $assoc_args): void
    {
        $group_id = intval($args[1] ?? 0);
        
        if (!$group_id) {
            \WP_CLI::error('Group ID is required');
            return;
        }
        
        $workflow = new ContentWorkflow();
        $result = $workflow->transition($group_id, 'group', 'published', [
            'reason' => 'Aprovado via CLI'
        ]);
        
        if ($result['success']) {
            \WP_CLI::success("Group {$group_id} approved successfully");
        } else {
            \WP_CLI::error("Failed to approve group {$group_id}: {$result['message']}");
        }
    }

    /**
     * Reject group
     */
    private function rejectGroup(array $args, array $assoc_args): void
    {
        $group_id = intval($args[1] ?? 0);
        $reason = $assoc_args['reason'] ?? 'Rejected via CLI';
        
        if (!$group_id) {
            \WP_CLI::error('Group ID is required');
            return;
        }
        
        $workflow = new ContentWorkflow();
        $result = $workflow->transition($group_id, 'group', 'rejected', [
            'reason' => $reason
        ]);
        
        if ($result['success']) {
            \WP_CLI::success("Group {$group_id} rejected: {$reason}");
        } else {
            \WP_CLI::error("Failed to reject group {$group_id}: {$result['message']}");
        }
    }

    /**
     * List groups by status
     */
    private function listGroups(array $assoc_args): void
    {
        global $wpdb;
        
        $status = $assoc_args['status'] ?? 'any';
        
        $where_clause = '';
        if ($status !== 'any') {
            $where_clause = $wpdb->prepare('WHERE status = %s', $status);
        }
        
        $groups = $wpdb->get_results("
            SELECT id, title, type, status, author_id, created_at 
            FROM {$wpdb->prefix}apollo_groups 
            {$where_clause}
            ORDER BY created_at DESC
            LIMIT 20
        ");
        
        if (empty($groups)) {
            \WP_CLI::log('No groups found');
            return;
        }
        
        \WP_CLI::log('ID\tTitle\t\t\tType\tStatus\t\tAuthor');
        \WP_CLI::log('----------------------------------------------------------------');
        
        foreach ($groups as $group) {
            $author = get_user_by('id', $group->author_id);
            $author_name = $author ? $author->user_login : 'Unknown';
            
            $title = strlen($group->title) > 20 ? substr($group->title, 0, 17) . '...' : $group->title;
            $title = str_pad($title, 20);
            
            \WP_CLI::log("{$group->id}\t{$title}\t{$group->type}\t{$group->status}\t\t{$author_name}");
        }
    }

    /**
     * Get short status for display
     */
    private function getShortStatus(string $status): string
    {
        return match($status) {
            'published' => 'PUB',
            'pending_review' => 'PND',
            'draft' => 'DRF',
            'rejected' => 'REJ',
            'suspended' => 'SUS',
            default => strtoupper(substr($status, 0, 3))
        };
    }
    
    /**
     * Verify commands
     */
    
    /**
     * Confirm verification
     * 
     * ## OPTIONS
     * 
     * --user=<id|login>
     * : User ID or login
     * 
     * ## EXAMPLES
     * 
     *     wp apollo-social verify confirm --user=123
     *     wp apollo-social verify confirm --user=admin
     */
    public function verify($args, $assoc_args)
    {
        if (empty($args) || $args[0] !== 'confirm' && $args[0] !== 'cancel' && $args[0] !== 'token') {
            \WP_CLI::error('Usage: wp apollo-social verify <confirm|cancel|token> --user=<id|login> [--reason="..."]');
        }
        
        $action = $args[0];
        $user_identifier = $assoc_args['user'] ?? null;
        
        if (!$user_identifier) {
            \WP_CLI::error('--user parameter is required');
        }
        
        // Get user by ID or login
        $user = is_numeric($user_identifier) 
            ? get_user_by('ID', intval($user_identifier))
            : get_user_by('login', $user_identifier);
        
        if (!$user) {
            \WP_CLI::error("User not found: {$user_identifier}");
        }
        
        $verifyInstagram = new VerifyInstagram();
        
        switch ($action) {
            case 'confirm':
                $result = $verifyInstagram->confirmVerification($user->ID, get_current_user_id() ?: 1);
                if ($result['success']) {
                    \WP_CLI::success("Verification confirmed for user: {$user->user_login} (ID: {$user->ID})");
                } else {
                    \WP_CLI::error($result['message'] ?? 'Failed to confirm verification');
                }
                break;
                
            case 'cancel':
                $reason = $assoc_args['reason'] ?? '';
                $result = $verifyInstagram->cancelVerification($user->ID, get_current_user_id() ?: 1, $reason);
                if ($result['success']) {
                    \WP_CLI::success("Verification canceled for user: {$user->user_login} (ID: {$user->ID})");
                } else {
                    \WP_CLI::error($result['message'] ?? 'Failed to cancel verification');
                }
                break;
                
            case 'token':
                $status = $verifyInstagram->getVerificationStatus($user->ID);
                if ($status['status'] !== 'not_found') {
                    $phrase = $status['phrase'] ?? '';
                    \WP_CLI::log("User: {$user->user_login} (ID: {$user->ID})");
                    \WP_CLI::log("Status: {$status['status']}");
                    \WP_CLI::log("Token: {$status['verify_token']}");
                    \WP_CLI::log("Phrase for DM: {$phrase}");
                } else {
                    \WP_CLI::error("No verification found for user: {$user_identifier}");
                }
                break;
        }
    }
    
    /**
     * List classifieds (WPAdverts)
     * 
     * ## OPTIONS
     * 
     * --per-page=<number>
     * : Number of ads per page (default: 10)
     * 
     * --page=<number>
     * : Page number (default: 1)
     * 
     * ## EXAMPLES
     * 
     *     wp apollo-social adverts list --per-page=10 --page=1
     */
    public function adverts($args, $assoc_args)
    {
        if (empty($args) || $args[0] !== 'list') {
            \WP_CLI::error('Usage: wp apollo-social adverts list [--per-page=10] [--page=1]');
        }
        
        if (!WPAdvertsAdapter::isActive()) {
            \WP_CLI::error('WPAdverts plugin is not active');
        }
        
        $per_page = intval($assoc_args['per-page'] ?? 10);
        $page = intval($assoc_args['page'] ?? 1);
        
        $result = WPAdvertsAdapter::listAds([
            'posts_per_page' => $per_page,
            'paged' => $page,
        ]);
        
        if (empty($result['ads'])) {
            \WP_CLI::log('No ads found');
            return;
        }
        
        \WP_CLI::log("Found {$result['total']} ads (Page {$page} of {$result['pages']}):");
        \WP_CLI::log('');
        \WP_CLI::log("ID\tTitle\t\t\tPrice\t\tAuthor");
        \WP_CLI::log(str_repeat('-', 80));
        
        foreach ($result['ads'] as $ad) {
            $title = strlen($ad['title']) > 25 ? substr($ad['title'], 0, 22) . '...' : $ad['title'];
            $title = str_pad($title, 25);
            $price = $ad['price'] ?: 'N/A';
            $price = str_pad($price, 15);
            $author = str_pad($ad['author_name'], 20);
            
            \WP_CLI::log("{$ad['id']}\t{$title}\t{$price}\t{$author}");
        }
    }
}