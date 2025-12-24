<?php
/**
 * Script de Auditoria de Estruturas Globais
 * Mapeia classes, funções, CPTs, taxonomies, meta keys, etc.
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __FILE__ ) . '/../../../../' );
}

$plugins_dir = __DIR__;
$results = [
	'classes' => [],
	'functions' => [],
	'cpts' => [],
	'taxonomies' => [],
	'meta_keys' => [],
	'hooks' => [],
	'placeholders' => [],
];

// Scan PHP files
$iterator = new RecursiveIteratorIterator(
	new RecursiveDirectoryIterator( $plugins_dir )
);

foreach ( $iterator as $file ) {
	if ( $file->isFile() && $file->getExtension() === 'php' ) {
		$path = $file->getPathname();
		
		// Skip vendor and cache directories
		if ( strpos( $path, '/vendor/' ) !== false || 
			 strpos( $path, '/.phpstan/' ) !== false ||
			 strpos( $path, '/node_modules/' ) !== false ) {
			continue;
		}
		
		$content = file_get_contents( $path );
		$relative_path = str_replace( $plugins_dir . '/', '', $path );
		
		// Extract classes
		if ( preg_match_all( '/^\s*(?:abstract\s+|final\s+)?class\s+(\w+)/m', $content, $matches ) ) {
			foreach ( $matches[1] as $class ) {
				if ( ! isset( $results['classes'][ $class ] ) ) {
					$results['classes'][ $class ] = [];
				}
				$results['classes'][ $class ][] = $relative_path;
			}
		}
		
		// Extract functions (global, not methods)
		if ( preg_match_all( '/^\s*function\s+(\w+)\s*\(/m', $content, $matches ) ) {
			foreach ( $matches[1] as $func ) {
				// Skip methods (inside classes)
				$before_func = substr( $content, 0, strpos( $content, "function $func" ) );
				if ( preg_match( '/class\s+\w+[^{]*\{[^}]*function\s+' . preg_quote( $func, '/' ) . '/s', $content ) ) {
					continue;
				}
				
				if ( ! isset( $results['functions'][ $func ] ) ) {
					$results['functions'][ $func ] = [];
				}
				$results['functions'][ $func ][] = $relative_path;
			}
		}
		
		// Extract CPTs
		if ( preg_match_all( "/register_post_type\s*\(\s*['\"]([^'\"]+)['\"]/", $content, $matches ) ) {
			foreach ( $matches[1] as $cpt ) {
				if ( ! isset( $results['cpts'][ $cpt ] ) ) {
					$results['cpts'][ $cpt ] = [];
				}
				$results['cpts'][ $cpt ][] = $relative_path;
			}
		}
		
		// Extract taxonomies
		if ( preg_match_all( "/register_taxonomy\s*\(\s*['\"]([^'\"]+)['\"]/", $content, $matches ) ) {
			foreach ( $matches[1] as $tax ) {
				if ( ! isset( $results['taxonomies'][ $tax ] ) ) {
					$results['taxonomies'][ $tax ] = [];
				}
				$results['taxonomies'][ $tax ][] = $relative_path;
			}
		}
		
		// Extract meta keys (common patterns)
		if ( preg_match_all( "/['\"](_?[a-z_]+)['\"].*get_post_meta|update_post_meta|delete_post_meta|add_post_meta/", $content, $matches ) ) {
			foreach ( $matches[1] as $meta_key ) {
				if ( strlen( $meta_key ) > 3 && strpos( $meta_key, 'apollo' ) !== false ) {
					if ( ! isset( $results['meta_keys'][ $meta_key ] ) ) {
						$results['meta_keys'][ $meta_key ] = [];
					}
					$results['meta_keys'][ $meta_key ][] = $relative_path;
				}
			}
		}
		
		// Extract hooks
		if ( preg_match_all( "/add_(?:action|filter)\s*\(\s*['\"]([^'\"]+)['\"]/", $content, $matches ) ) {
			foreach ( $matches[1] as $hook ) {
				if ( ! isset( $results['hooks'][ $hook ] ) ) {
					$results['hooks'][ $hook ] = [];
				}
				$results['hooks'][ $hook ][] = $relative_path;
			}
		}
	}
}

// Find collisions
$collisions = [
	'classes' => [],
	'functions' => [],
	'cpts' => [],
	'taxonomies' => [],
];

foreach ( $results['classes'] as $class => $files ) {
	if ( count( $files ) > 1 ) {
		$collisions['classes'][ $class ] = $files;
	}
}

foreach ( $results['functions'] as $func => $files ) {
	if ( count( $files ) > 1 && ! in_array( $func, [ 'apollo_log_missing_file', 'apollo_cfg' ] ) ) {
		$collisions['functions'][ $func ] = $files;
	}
}

foreach ( $results['cpts'] as $cpt => $files ) {
	if ( count( $files ) > 1 ) {
		$collisions['cpts'][ $cpt ] = $files;
	}
}

foreach ( $results['taxonomies'] as $tax => $files ) {
	if ( count( $files ) > 1 ) {
		$collisions['taxonomies'][ $tax ] = $files;
	}
}

// Output results
echo "=== AUDITORIA DE ESTRUTURAS GLOBAIS ===\n\n";

echo "CLASSES ENCONTRADAS: " . count( $results['classes'] ) . "\n";
if ( ! empty( $collisions['classes'] ) ) {
	echo "⚠️  COLISÕES DE CLASSES:\n";
	foreach ( $collisions['classes'] as $class => $files ) {
		echo "  - $class: " . implode( ', ', $files ) . "\n";
	}
}

echo "\nFUNÇÕES GLOBAIS: " . count( $results['functions'] ) . "\n";
if ( ! empty( $collisions['functions'] ) ) {
	echo "⚠️  COLISÕES DE FUNÇÕES:\n";
	foreach ( $collisions['functions'] as $func => $files ) {
		echo "  - $func: " . implode( ', ', $files ) . "\n";
	}
}

echo "\nCPTs REGISTRADOS: " . count( $results['cpts'] ) . "\n";
foreach ( $results['cpts'] as $cpt => $files ) {
	echo "  - $cpt\n";
}
if ( ! empty( $collisions['cpts'] ) ) {
	echo "⚠️  COLISÕES DE CPTs:\n";
	foreach ( $collisions['cpts'] as $cpt => $files ) {
		echo "  - $cpt: " . implode( ', ', $files ) . "\n";
	}
}

echo "\nTAXONOMIES REGISTRADAS: " . count( $results['taxonomies'] ) . "\n";
foreach ( $results['taxonomies'] as $tax => $files ) {
	echo "  - $tax\n";
}
if ( ! empty( $collisions['taxonomies'] ) ) {
	echo "⚠️  COLISÕES DE TAXONOMIES:\n";
	foreach ( $collisions['taxonomies'] as $tax => $files ) {
		echo "  - $tax: " . implode( ', ', $files ) . "\n";
	}
}

echo "\nMETA KEYS ENCONTRADOS: " . count( $results['meta_keys'] ) . "\n";
echo "(Mostrando primeiros 20)\n";
$i = 0;
foreach ( $results['meta_keys'] as $key => $files ) {
	if ( $i++ >= 20 ) break;
	echo "  - $key\n";
}

echo "\nHOOKS REGISTRADOS: " . count( $results['hooks'] ) . "\n";

// Save to file
file_put_contents(
	$plugins_dir . '/AUDIT-GLOBAL-STRUCTURES.json',
	json_encode( $results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES )
);

echo "\n✅ Resultados salvos em: AUDIT-GLOBAL-STRUCTURES.json\n";
