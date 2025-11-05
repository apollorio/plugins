#!/usr/bin/env php
<?php
/**
 * Apollo Social Test Playbook Runner
 * 
 * Quick test runner script for workflow and permission validation.
 * Run with: php test-playbook.php
 */

// Basic WordPress environment (simplified for testing)
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__DIR__, 4) . '/');
}

echo "🧪 Apollo Social - Quick Test Playbook\n";
echo "=====================================\n\n";

/**
 * Test 1: Workflow Matrix Logic
 */
echo "📋 Test 1: Workflow Matrix Logic\n";
echo "--------------------------------\n";

$test_cases = [
    // Format: [role, content_type, data, expected_status]
    ['subscriber', 'group', ['type' => 'post'], 'published'],
    ['subscriber', 'group', ['type' => 'discussion'], 'published'],
    ['subscriber', 'ad', [], 'published'],
    ['subscriber', 'event', [], 'pending_review'],
    ['subscriber', 'group', ['type' => 'nucleo'], 'pending_review'],
    ['subscriber', 'group', ['type' => 'comunidade'], 'pending_review'],
    
    ['contributor', 'group', ['type' => 'post'], 'draft'],
    ['contributor', 'ad', [], 'draft'],
    ['contributor', 'event', [], 'draft'],
    
    ['author', 'group', ['type' => 'post'], 'pending_review'],
    ['author', 'ad', [], 'pending_review'],
    ['author', 'event', [], 'published'], // Direct publish for events
    
    ['editor', 'group', ['type' => 'post'], 'published'],
    ['editor', 'ad', [], 'published'],
    ['editor', 'event', [], 'published'],
    ['editor', 'group', ['type' => 'nucleo'], 'published'],
    
    ['administrator', 'group', ['type' => 'post'], 'published'],
    ['administrator', 'ad', [], 'published'],
    ['administrator', 'event', [], 'published'],
];

$passed = 0;
$failed = 0;

foreach ($test_cases as $test) {
    [$role, $content_type, $data, $expected] = $test;
    
    $actual = getWorkflowInitialState($role, $content_type, $data);
    
    $content_desc = $content_type;
    if (!empty($data['type'])) {
        $content_desc .= " ({$data['type']})";
    }
    
    if ($actual === $expected) {
        echo "  ✅ {$role} → {$content_desc} → {$actual}\n";
        $passed++;
    } else {
        echo "  ❌ {$role} → {$content_desc} → Expected: {$expected}, Got: {$actual}\n";
        $failed++;
    }
}

echo "\n";

/**
 * Test 2: Permission Matrix
 */
echo "📋 Test 2: Permission Matrix\n";
echo "----------------------------\n";

$permission_tests = [
    // [role, capability, expected]
    ['subscriber', 'create_apollo_groups', true],
    ['subscriber', 'create_apollo_ads', true],
    ['subscriber', 'publish_apollo_groups', true], // For user posts
    ['subscriber', 'publish_eva_events', false],
    
    ['contributor', 'create_apollo_groups', true],
    ['contributor', 'publish_apollo_groups', false],
    
    ['author', 'create_eva_events', true],
    ['author', 'publish_eva_events', true], // Direct event publishing
    ['author', 'publish_apollo_groups', false], // Groups need approval
    
    ['editor', 'publish_apollo_groups', true],
    ['editor', 'apollo_moderate', true],
    
    ['administrator', 'apollo_moderate_all', true],
];

foreach ($permission_tests as $test) {
    [$role, $capability, $expected] = $test;
    
    $actual = checkRoleCapability($role, $capability);
    
    if ($actual === $expected) {
        echo "  ✅ {$role} → {$capability} → " . ($actual ? 'Yes' : 'No') . "\n";
        $passed++;
    } else {
        echo "  ❌ {$role} → {$capability} → Expected: " . ($expected ? 'Yes' : 'No') . ", Got: " . ($actual ? 'Yes' : 'No') . "\n";
        $failed++;
    }
}

echo "\n";

/**
 * Test 3: Content Type Validation
 */
echo "📋 Test 3: Content Type Validation\n";
echo "----------------------------------\n";

$content_tests = [
    ['subscriber', 'Social Post', 'group', ['type' => 'post'], 'published'],
    ['subscriber', 'Classified Ad', 'ad', ['season' => 'verao-2026'], 'published'],
    ['subscriber', 'Event', 'event', [], 'pending_review'],
    ['subscriber', 'Núcleo', 'group', ['type' => 'nucleo'], 'pending_review'],
    ['author', 'Event', 'event', [], 'published'],
    ['contributor', 'Any Content', 'group', ['type' => 'post'], 'draft'],
];

foreach ($content_tests as $test) {
    [$role, $name, $content_type, $data, $expected] = $test;
    
    $actual = getWorkflowInitialState($role, $content_type, $data);
    
    if ($actual === $expected) {
        echo "  ✅ {$role} creating {$name} → {$actual}\n";
        $passed++;
    } else {
        echo "  ❌ {$role} creating {$name} → Expected: {$expected}, Got: {$actual}\n";
        $failed++;
    }
}

echo "\n";

/**
 * Summary
 */
echo "📊 Test Summary\n";
echo "==============\n";
echo "✅ Passed: {$passed}\n";
echo "❌ Failed: {$failed}\n";
echo "Total: " . ($passed + $failed) . "\n\n";

if ($failed === 0) {
    echo "🎉 All tests passed! Workflow logic is correct.\n";
    echo "\n🚀 Ready for integration testing:\n";
    echo "  1. wp apollo install\n";
    echo "  2. wp apollo setup-permissions\n";
    echo "  3. wp apollo seed --users --seasons\n";
    echo "  4. wp apollo test-matrix\n";
    exit(0);
} else {
    echo "⚠️  Some tests failed. Review workflow implementation.\n";
    exit(1);
}

/**
 * Helper Functions
 */

/**
 * Simulate workflow initial state logic
 */
function getWorkflowInitialState(string $role, string $content_type, array $data): string
{
    // Administrator and Editor rules - can always publish directly
    if (in_array($role, ['administrator', 'editor'])) {
        return 'published';
    }

    // Author rules
    if ($role === 'author') {
        // Authors can publish events directly
        if ($content_type === 'event') {
            return 'published';
        }
        
        // Other content from authors goes to pending review
        return 'pending_review';
    }

    // Subscriber rules  
    if ($role === 'subscriber') {
        // User posts (groups) - check type
        if ($content_type === 'group') {
            $group_type = $data['type'] ?? '';
            
            // Regular user posts can be published directly
            if (in_array($group_type, ['post', 'discussion', 'question'])) {
                return 'published';
            }
            
            // Community/Núcleo groups need approval
            if (in_array($group_type, ['comunidade', 'nucleo'])) {
                return 'pending_review';
            }
            
            // Default for other group types
            return 'published';
        }
        
        // Classifieds (ads) - contract & published directly
        if ($content_type === 'ad') {
            return 'published';
        }
        
        // Events from subscribers need approval
        if ($content_type === 'event') {
            return 'pending_review';
        }
    }

    // Contributor rules - only creates drafts
    if ($role === 'contributor') {
        return 'draft';
    }

    // Default fallback
    return 'pending_review';
}

/**
 * Check role capability (simplified)
 */
function checkRoleCapability(string $role, string $capability): bool
{
    $role_capabilities = [
        'subscriber' => [
            'create_apollo_groups' => true,
            'create_apollo_ads' => true,
            'publish_apollo_groups' => true, // For user posts
            'publish_apollo_ads' => true,
            'create_eva_events' => true,
            'publish_eva_events' => false,
        ],
        'contributor' => [
            'create_apollo_groups' => true,
            'create_apollo_ads' => true,
            'create_eva_events' => true,
            'publish_apollo_groups' => false,
            'publish_apollo_ads' => false,
            'publish_eva_events' => false,
        ],
        'author' => [
            'create_apollo_groups' => true,
            'create_apollo_ads' => true,
            'create_eva_events' => true,
            'publish_eva_events' => true, // Authors can publish events directly
            'publish_apollo_groups' => false, // Groups need approval
            'publish_apollo_ads' => false, // Ads need approval
        ],
        'editor' => [
            'create_apollo_groups' => true,
            'create_apollo_ads' => true,
            'create_eva_events' => true,
            'publish_apollo_groups' => true,
            'publish_apollo_ads' => true,
            'publish_eva_events' => true,
            'apollo_moderate' => true,
        ],
        'administrator' => [
            'create_apollo_groups' => true,
            'create_apollo_ads' => true,
            'create_eva_events' => true,
            'publish_apollo_groups' => true,
            'publish_apollo_ads' => true,
            'publish_eva_events' => true,
            'apollo_moderate' => true,
            'apollo_moderate_all' => true,
        ],
    ];

    return $role_capabilities[$role][$capability] ?? false;
}