<?php
/**
 * Apollo Master Test Runner
 * 
 * Executa todos os testes em sequência
 * Usage: wp eval-file APOLLO-RUN-ALL-TESTS.php
 */

if (!defined('ABSPATH')) {
    require_once('../../../wp-load.php');
}

echo "\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "🚀 APOLLO MASTER TEST RUNNER\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "\n";

$test_files = [
    'APOLLO-ECOSYSTEM-UNIFICATION.php' => 'Ecosystem Unification',
    'APOLLO-XDEBUG-TEST.php' => 'XDebug Testing',
    'APOLLO-DATABASE-TEST.php' => 'Database Testing',
    'APOLLO-FINAL-CHECKUP.php' => 'Final Checkup',
];

$results = [];

foreach ($test_files as $file => $name) {
    echo "▶️ Executando: {$name}...\n";
    echo "────────────────────────────────────────────────────────────────\n";
    
    $file_path = __DIR__ . '/' . $file;
    if (file_exists($file_path)) {
        ob_start();
        include $file_path;
        $output = ob_get_clean();
        echo $output;
        $results[$name] = 'OK';
    } else {
        echo "   ❌ Arquivo não encontrado: {$file}\n";
        $results[$name] = 'FAIL';
    }
    
    echo "\n";
    sleep(1); // Pequena pausa entre testes
}

// Resumo final
echo "════════════════════════════════════════════════════════════════\n";
echo "📊 RESUMO DE TODOS OS TESTES\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "\n";

foreach ($results as $test => $status) {
    $icon = $status === 'OK' ? '✅' : '❌';
    echo "{$icon} {$test}: {$status}\n";
}

$all_passed = !in_array('FAIL', $results);

echo "\n";
if ($all_passed) {
    echo "════════════════════════════════════════════════════════════════\n";
    echo "✨ TODOS OS TESTES PASSARAM!\n";
    echo "════════════════════════════════════════════════════════════════\n";
    echo "\n";
    echo "🚀 SISTEMA PRONTO PARA IR AO AR!\n";
} else {
    echo "════════════════════════════════════════════════════════════════\n";
    echo "⚠️ ALGUNS TESTES FALHARAM\n";
    echo "════════════════════════════════════════════════════════════════\n";
    echo "\n";
    echo "Por favor, revise os resultados acima antes de ir ao ar.\n";
}

echo "\n";

