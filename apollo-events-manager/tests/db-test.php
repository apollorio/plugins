<?php
/**
 * Apollo Events Manager - Database Test
 * 
 * Testa a conexão e estrutura do banco de dados
 */

// Database credentials
define('DB_HOST', 'localhost:10005');
define('DB_NAME', 'local');
define('DB_USER', 'root');
define('DB_PASS', 'root');

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apollo Events Manager - Database Test</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #1a1a1a;
            margin-bottom: 20px;
            border-bottom: 3px solid #0073aa;
            padding-bottom: 10px;
        }
        .test-section {
            margin-bottom: 30px;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 5px;
            border-left: 4px solid #0073aa;
        }
        .status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 12px;
            text-transform: uppercase;
            margin-right: 10px;
        }
        .status.pass { background: #46b450; color: white; }
        .status.fail { background: #dc3232; color: white; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #0073aa;
            color: white;
        }
        .details {
            margin-top: 10px;
            padding: 10px;
            background: #fff;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            max-height: 400px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🗄️ Apollo Events Manager - Database Test</h1>
        <p><strong>Data:</strong> <?php echo date('d/m/Y H:i:s'); ?></p>
        <p><strong>Host:</strong> <?php echo DB_HOST; ?></p>
        <p><strong>Database:</strong> <?php echo DB_NAME; ?></p>
        
        <?php
        try {
            // Test connection
            $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
            if ($mysqli->connect_error) {
                throw new Exception("Connection failed: " . $mysqli->connect_error);
            }
            
            echo '<div class="test-section">';
            echo '<h2>✅ Conexão Estabelecida</h2>';
            echo '<p><span class="status pass">PASS</span>Conexão com banco de dados bem-sucedida</p>';
            echo '<div class="details">';
            echo 'Server Info: ' . $mysqli->server_info . '<br>';
            echo 'Host Info: ' . $mysqli->host_info . '<br>';
            echo 'Protocol Version: ' . $mysqli->protocol_version . '<br>';
            echo '</div>';
            echo '</div>';
            
            // Test WordPress tables
            echo '<div class="test-section">';
            echo '<h2>📊 Tabelas do WordPress</h2>';
            
            $wp_tables = [
                'wp_posts',
                'wp_postmeta',
                'wp_users',
                'wp_usermeta',
                'wp_options',
                'wp_terms',
                'wp_term_taxonomy',
                'wp_term_relationships',
            ];
            
            echo '<table>';
            echo '<tr><th>Tabela</th><th>Registros</th><th>Status</th></tr>';
            
            foreach ($wp_tables as $table) {
                $result = $mysqli->query("SELECT COUNT(*) as count FROM $table");
                if ($result) {
                    $row = $result->fetch_assoc();
                    $count = $row['count'];
                    echo '<tr>';
                    echo '<td><code>' . $table . '</code></td>';
                    echo '<td>' . number_format($count) . '</td>';
                    echo '<td><span class="status pass">OK</span></td>';
                    echo '</tr>';
                } else {
                    echo '<tr>';
                    echo '<td><code>' . $table . '</code></td>';
                    echo '<td>-</td>';
                    echo '<td><span class="status fail">ERRO</span></td>';
                    echo '</tr>';
                }
            }
            echo '</table>';
            echo '</div>';
            
            // Test Custom Post Types
            echo '<div class="test-section">';
            echo '<h2>🎯 Custom Post Types</h2>';
            
            $cpts = ['event_listing', 'event_dj', 'event_local'];
            
            echo '<table>';
            echo '<tr><th>Post Type</th><th>Publicados</th><th>Pendentes</th><th>Rascunhos</th><th>Total</th></tr>';
            
            foreach ($cpts as $cpt) {
                $query = "SELECT post_status, COUNT(*) as count 
                         FROM wp_posts 
                         WHERE post_type = '$cpt' 
                         GROUP BY post_status";
                $result = $mysqli->query($query);
                
                $statuses = ['publish' => 0, 'pending' => 0, 'draft' => 0];
                $total = 0;
                
                if ($result) {
                    while ($row = $result->fetch_assoc()) {
                        $statuses[$row['post_status']] = $row['count'];
                        $total += $row['count'];
                    }
                }
                
                echo '<tr>';
                echo '<td><code>' . $cpt . '</code></td>';
                echo '<td>' . $statuses['publish'] . '</td>';
                echo '<td>' . $statuses['pending'] . '</td>';
                echo '<td>' . $statuses['draft'] . '</td>';
                echo '<td><strong>' . $total . '</strong></td>';
                echo '</tr>';
            }
            echo '</table>';
            echo '</div>';
            
            // Test Meta Keys
            echo '<div class="test-section">';
            echo '<h2>🔑 Meta Keys Canônicas</h2>';
            
            $meta_keys = [
                '_event_dj_ids',
                '_event_local_ids',
                '_event_timetable',
                '_event_start_date',
                '_event_banner',
            ];
            
            echo '<table>';
            echo '<tr><th>Meta Key</th><th>Eventos com esta meta</th><th>Status</th></tr>';
            
            foreach ($meta_keys as $meta_key) {
                $query = "SELECT COUNT(DISTINCT post_id) as count 
                         FROM wp_postmeta 
                         WHERE meta_key = '$meta_key'";
                $result = $mysqli->query($query);
                
                if ($result) {
                    $row = $result->fetch_assoc();
                    $count = $row['count'];
                    $status = $count > 0 ? 'pass' : 'fail';
                    echo '<tr>';
                    echo '<td><code>' . $meta_key . '</code></td>';
                    echo '<td>' . $count . '</td>';
                    echo '<td><span class="status ' . $status . '">' . ($count > 0 ? 'OK' : 'VAZIO') . '</span></td>';
                    echo '</tr>';
                }
            }
            echo '</table>';
            echo '</div>';
            
            // Test Legacy Meta Keys (should be migrated)
            echo '<div class="test-section">';
            echo '<h2>⚠️ Meta Keys Legadas (Devem ser Migradas)</h2>';
            
            $legacy_keys = ['_event_djs', '_event_local', '_timetable'];
            
            echo '<table>';
            echo '<tr><th>Meta Key Legada</th><th>Eventos com esta meta</th><th>Ação</th></tr>';
            
            foreach ($legacy_keys as $legacy_key) {
                $query = "SELECT COUNT(DISTINCT post_id) as count 
                         FROM wp_postmeta 
                         WHERE meta_key = '$legacy_key'";
                $result = $mysqli->query($query);
                
                if ($result) {
                    $row = $result->fetch_assoc();
                    $count = $row['count'];
                    echo '<tr>';
                    echo '<td><code>' . $legacy_key . '</code></td>';
                    echo '<td>' . $count . '</td>';
                    echo '<td>' . ($count > 0 ? '<span style="color: #ffb900;">⚠️ Migração necessária</span>' : '<span style="color: #46b450;">✅ Já migrado</span>') . '</td>';
                    echo '</tr>';
                }
            }
            echo '</table>';
            echo '</div>';
            
            $mysqli->close();
            
        } catch (Exception $e) {
            echo '<div class="test-section">';
            echo '<h2>❌ Erro de Conexão</h2>';
            echo '<p><span class="status fail">FAIL</span>' . $e->getMessage() . '</p>';
            echo '</div>';
        }
        ?>
    </div>
</body>
</html>

