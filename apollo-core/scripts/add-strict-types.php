<?php
/**
 * Script to add declare(strict_types=1) to all PHP files
 *
 * Usage: php scripts/add-strict-types.php
 */

$plugin_dir = dirname( __DIR__ );
$files_updated = 0;
$files_skipped = 0;

// Get all PHP files
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator( $plugin_dir )
);

foreach ( $iterator as $file ) {
    if ( $file->isFile() && 'php' === $file->getExtension() ) {
        $filepath = $file->getPathname();
        
        // Skip this script and vendor
        if ( false !== strpos( $filepath, 'scripts' ) || false !== strpos( $filepath, 'vendor' ) ) {
            continue;
        }
        
        $content = file_get_contents( $filepath );
        
        // Check if already has strict_types
        if ( false !== strpos( $content, 'declare(strict_types=1)' ) ) {
            $files_skipped++;
            echo "⏭️  Skipped (already strict): " . basename( $filepath ) . "\n";
            continue;
        }
        
        // Check if starts with <?php
        if ( 0 !== strpos( $content, '<?php' ) ) {
            $files_skipped++;
            echo "⏭️  Skipped (no <?php tag): " . basename( $filepath ) . "\n";
            continue;
        }
        
        // Add declare after <?php
        $new_content = preg_replace(
            '/^<\?php\n/',
            "<?php\ndeclare(strict_types=1);\n\n",
            $content
        );
        
        // If no match, try without newline after <?php
        if ( $new_content === $content ) {
            $new_content = preg_replace(
                '/^<\?php/',
                "<?php\ndeclare(strict_types=1);\n",
                $content
            );
        }
        
        if ( $new_content !== $content ) {
            file_put_contents( $filepath, $new_content );
            $files_updated++;
            echo "✅ Updated: " . basename( $filepath ) . "\n";
        } else {
            $files_skipped++;
            echo "⏭️  Skipped (couldn't parse): " . basename( $filepath ) . "\n";
        }
    }
}

echo "\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📊 SUMMARY\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "✅ Files updated: " . $files_updated . "\n";
echo "⏭️  Files skipped: " . $files_skipped . "\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

