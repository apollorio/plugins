<?php
/**
 * Apollo CDN Performance Monitor
 * PHASE 7: Performance Optimization - CDN Monitoring
 * Monitors CDN asset performance and provides health metrics
 */

declare(strict_types=1);

namespace Apollo_Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CDN_Performance_Monitor {

	private const CDN_BASE_URL          = 'https://assets.apollo.rio.br/';
	private const MONITORING_INTERVAL   = 300; // 5 minutes.
	private const PERFORMANCE_THRESHOLD = 2000; // 2 seconds.
	private const HEALTH_CHECK_ENDPOINT = 'health.json';

	/**
	 * Initialize CDN monitoring
	 */
	public static function init(): void {
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_performance_monitoring' ) );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue_performance_monitoring' ) );
		add_action( 'wp_ajax_apollo_cdn_health_check', array( self::class, 'handle_health_check' ) );
		add_action( 'apollo_cdn_monitor_health', array( self::class, 'perform_health_check' ) );
		add_action( 'wp_footer', array( self::class, 'inject_performance_tracking' ), 999 );

		// Schedule health checks.
		if ( ! wp_next_scheduled( 'apollo_cdn_monitor_health' ) ) {
			wp_schedule_event( time(), 'five_minutes', 'apollo_cdn_monitor_health' );
		}

		// Add admin menu.
		add_action( 'admin_menu', array( self::class, 'add_admin_menu' ) );
	}

	/**
	 * Enqueue performance monitoring scripts
	 */
	public static function enqueue_performance_monitoring(): void {
		wp_enqueue_script(
			'apollo-cdn-monitor',
			APOLLO_CORE_PLUGIN_URL . 'js/cdn-monitor.js',
			array(),
			APOLLO_CORE_VERSION,
			true
		);

		wp_localize_script(
			'apollo-cdn-monitor',
			'apolloCDNMonitor',
			array(
				'ajax_url'  => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'apollo_cdn_monitor' ),
				'cdn_base'  => self::CDN_BASE_URL,
				'threshold' => self::PERFORMANCE_THRESHOLD,
			)
		);
	}

	/**
	 * Inject performance tracking in footer
	 */
	public static function inject_performance_tracking(): void {
		if ( ! is_admin() && ! wp_doing_ajax() ) {
			echo self::get_performance_tracking_script();
		}
	}

	/**
	 * Get performance tracking JavaScript
	 */
	private static function get_performance_tracking_script(): string {
		ob_start();
		?>
		<script>
		(function() {
			// Track CDN asset load times.
			const cdnAssets = [];
			const observer = new PerformanceObserver(function(list) {
				const entries = list.getEntries();
				entries.forEach(function(entry) {
					if (entry.name.includes('<?php echo esc_js( self::CDN_BASE_URL ); ?>')) {
						cdnAssets.push({
							url: entry.name,
							loadTime: entry.duration,
							size: entry.transferSize || 0,
							timestamp: Date.now()
						});
					}
				});
			});

			observer.observe({entryTypes: ['resource']});

			// Send performance data on page unload.
			window.addEventListener('beforeunload', function() {
				if (cdnAssets.length > 0) {
					navigator.sendBeacon('<?php echo admin_url( 'admin-ajax.php' ); ?>', JSON.stringify({
						action: 'apollo_cdn_performance_data',
						nonce: '<?php echo wp_create_nonce( 'apollo_cdn_performance' ); ?>',
						data: cdnAssets
					}));
				}
			});

			// Track Core Web Vitals.
			if ('web-vitals' in window) {
				webVitals.getCLS(console.log);
				webVitals.getFID(console.log);
				webVitals.getFCP(console.log);
				webVitals.getLCP(console.log);
				webVitals.getTTFB(console.log);
			}
		})();
		</script>
		<?php
		return ob_get_clean();
	}

	/**
	 * Handle AJAX health check requests
	 */
	public static function handle_health_check(): void {
		check_ajax_referer( 'apollo_cdn_monitor', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Insufficient permissions', 'apollo-core' ) );
		}

		$health_data = self::perform_health_check();
		wp_send_json( $health_data );
	}

	/**
	 * Perform comprehensive CDN health check
	 */
	public static function perform_health_check(): array {
		$health_data = array(
			'timestamp'       => current_time( 'timestamp' ),
			'cdn_status'      => 'unknown',
			'response_time'   => 0,
			'assets_checked'  => array(),
			'overall_health'  => 'good',
			'recommendations' => array(),
		);

		// Check CDN health endpoint.
		$health_url = self::CDN_BASE_URL . self::HEALTH_CHECK_ENDPOINT;
		$start_time = microtime( true );

		$response = wp_remote_get(
			$health_url,
			array(
				'timeout'     => 10,
				'redirection' => 5,
				'headers'     => array(
					'User-Agent' => 'Apollo-CDN-Monitor/1.0',
				),
			)
		);

		$health_data['response_time'] = round( ( microtime( true ) - $start_time ) * 1000, 2 );

		if ( is_wp_error( $response ) ) {
			$health_data['cdn_status']        = 'error';
			$health_data['overall_health']    = 'critical';
			$health_data['recommendations'][] = 'CDN health check failed: ' . $response->get_error_message();
		} else {
			$status_code               = wp_remote_retrieve_response_code( $response );
			$health_data['cdn_status'] = $status_code;

			if ( $status_code >= 200 && $status_code < 300 ) {
				$health_data['cdn_status'] = 'healthy';

				// Parse health response.
				$body        = wp_remote_retrieve_body( $response );
				$health_info = json_decode( $body, true );

				if ( $health_info ) {
					$health_data = array_merge( $health_data, $health_info );
				}
			} else {
				$health_data['overall_health']    = 'critical';
				$health_data['recommendations'][] = "CDN returned status code: {$status_code}";
			}
		}

		// Check critical assets.
		$critical_assets = self::get_critical_assets();
		foreach ( $critical_assets as $asset ) {
			$asset_health                    = self::check_asset_health( $asset );
			$health_data['assets_checked'][] = $asset_health;

			if ( $asset_health['status'] !== 'healthy' ) {
				$health_data['overall_health']    = 'warning';
				$health_data['recommendations'][] = "Asset {$asset['name']} is not loading properly";
			}
		}

		// Performance analysis.
		if ( $health_data['response_time'] > self::PERFORMANCE_THRESHOLD ) {
			$health_data['overall_health']    = 'warning';
			$health_data['recommendations'][] = 'CDN response time is above threshold';
		}

		// Store health data.
		self::store_health_data( $health_data );

		return $health_data;
	}

	/**
	 * Check individual asset health
	 */
	private static function check_asset_health( array $asset ): array {
		$start_time = microtime( true );

		$response = wp_remote_head(
			$asset['url'],
			array(
				'timeout'     => 5,
				'redirection' => 3,
			)
		);

		$load_time = round( ( microtime( true ) - $start_time ) * 1000, 2 );

		$asset_health = array(
			'name'      => $asset['name'],
			'url'       => $asset['url'],
			'status'    => 'unknown',
			'load_time' => $load_time,
			'size'      => 0,
			'cached'    => false,
		);

		if ( is_wp_error( $response ) ) {
			$asset_health['status'] = 'error';
		} else {
			$status_code = wp_remote_retrieve_response_code( $response );
			$headers     = wp_remote_retrieve_headers( $response );

			if ( $status_code >= 200 && $status_code < 300 ) {
				$asset_health['status'] = 'healthy';
				$asset_health['size']   = $headers['content-length'] ?? 0;
				$asset_health['cached'] = isset( $headers['x-cache'] ) && strpos( $headers['x-cache'], 'HIT' ) !== false;
			} else {
				$asset_health['status'] = 'error';
			}
		}

		return $asset_health;
	}

	/**
	 * Get list of critical assets to monitor
	 */
	private static function get_critical_assets(): array {
		return array(
			array(
				'name' => 'UNI CSS',
				'url'  => self::CDN_BASE_URL . 'css/uni.css',
			),
			array(
				'name' => 'Apollo Core JS',
				'url'  => self::CDN_BASE_URL . 'js/apollo-core.js',
			),
			array(
				'name' => 'Event Manager CSS',
				'url'  => self::CDN_BASE_URL . 'css/events.css',
			),
			array(
				'name' => 'Social Features JS',
				'url'  => self::CDN_BASE_URL . 'js/social.js',
			),
		);
	}

	/**
	 * Store health data for historical tracking
	 */
	private static function store_health_data( array $health_data ): void {
		$history   = get_option( 'apollo_cdn_health_history', array() );
		$history[] = $health_data;

		// Keep only last 100 health checks.
		if ( count( $history ) > 100 ) {
			$history = array_slice( $history, -100 );
		}

		update_option( 'apollo_cdn_health_history', $history );
	}

	/**
	 * Get CDN performance statistics
	 */
	public static function get_performance_stats(): array {
		$history = get_option( 'apollo_cdn_health_history', array() );

		if ( empty( $history ) ) {
			return array(
				'average_response_time' => 0,
				'uptime_percentage'     => 0,
				'total_checks'          => 0,
				'last_check'            => null,
			);
		}

		$total_checks        = count( $history );
		$healthy_checks      = 0;
		$total_response_time = 0;

		foreach ( $history as $check ) {
			if ( $check['cdn_status'] === 'healthy' ) {
				++$healthy_checks;
			}
			$total_response_time += $check['response_time'];
		}

		return array(
			'average_response_time' => round( $total_response_time / $total_checks, 2 ),
			'uptime_percentage'     => round( ( $healthy_checks / $total_checks ) * 100, 2 ),
			'total_checks'          => $total_checks,
			'last_check'            => end( $history ),
		);
	}

	/**
	 * Add admin menu for CDN monitoring
	 */
	public static function add_admin_menu(): void {
		add_submenu_page(
			'tools.php',
			__( 'CDN Performance Monitor', 'apollo-core' ),
			__( 'CDN Monitor', 'apollo-core' ),
			'manage_options',
			'apollo-cdn-monitor',
			array( __CLASS__, 'render_admin_page' )
		);
	}

	/**
	 * Render admin page
	 */
	public static function render_admin_page(): void {
		$stats      = self::get_performance_stats();
		$last_check = $stats['last_check'];
		$history    = get_option( 'apollo_cdn_health_history', array() );

		// Prepare chart data.
		$chart_labels         = array();
		$chart_response_times = array();
		$chart_statuses       = array();

		foreach ( $history as $check ) {
			$chart_labels[]         = date_i18n( 'd/m H:i', $check['timestamp'] );
			$chart_response_times[] = $check['response_time'];
			$chart_statuses[]       = $check['cdn_status'] === 'healthy' ? 1 : 0;
		}

		?>
		<div class="wrap">
			<h1><?php _e( 'Apollo CDN Performance Monitor', 'apollo-core' ); ?></h1>

			<div class="apollo-cdn-stats">
				<div class="stat-box">
					<h3><?php _e( 'Average Response Time', 'apollo-core' ); ?></h3>
					<span class="stat-value"><?php echo esc_html( $stats['average_response_time'] ); ?>ms</span>
				</div>

				<div class="stat-box">
					<h3><?php _e( 'Uptime', 'apollo-core' ); ?></h3>
					<span class="stat-value"><?php echo esc_html( $stats['uptime_percentage'] ); ?>%</span>
				</div>

				<div class="stat-box">
					<h3><?php _e( 'Total Checks', 'apollo-core' ); ?></h3>
					<span class="stat-value"><?php echo esc_html( $stats['total_checks'] ); ?></span>
				</div>
			</div>

			<div class="apollo-cdn-actions">
				<button id="apollo-cdn-health-check" class="button button-primary">
					<?php _e( 'Run Health Check', 'apollo-core' ); ?>
				</button>

				<button id="apollo-cdn-clear-history" class="button">
					<?php _e( 'Clear History', 'apollo-core' ); ?>
				</button>
			</div>

			<?php if ( count( $history ) > 1 ) : ?>
			<div class="apollo-cdn-chart-section card" style="margin: 20px 0; padding: 20px;">
				<h2 style="margin-top: 0;"><?php _e( 'Performance History Chart', 'apollo-core' ); ?></h2>
				<div style="position: relative; height: 300px; margin-bottom: 20px;">
					<canvas id="apollo-cdn-response-chart"></canvas>
				</div>
				<div style="position: relative; height: 200px;">
					<canvas id="apollo-cdn-status-chart"></canvas>
				</div>
			</div>
			<?php endif; ?>

			<?php if ( ! empty( $history ) ) : ?>
			<div class="apollo-cdn-history-table card" style="margin: 20px 0; padding: 20px;">
				<h2 style="margin-top: 0;"><?php _e( 'Health Check History', 'apollo-core' ); ?></h2>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th style="width: 150px;"><?php _e( 'Timestamp', 'apollo-core' ); ?></th>
							<th style="width: 100px;"><?php _e( 'Status', 'apollo-core' ); ?></th>
							<th style="width: 100px;"><?php _e( 'Response', 'apollo-core' ); ?></th>
							<th><?php _e( 'Health', 'apollo-core' ); ?></th>
							<th><?php _e( 'Issues', 'apollo-core' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						$reversed_history = array_reverse( $history );
						$shown            = 0;
						foreach ( $reversed_history as $check ) :
							if ( $shown >= 20 ) {
								break;
							}
							++$shown;
							$status_class = $check['cdn_status'] === 'healthy' ? 'status-good' : 'status-critical';
							$health_class = $check['overall_health'] === 'good' ? 'status-good' : ( $check['overall_health'] === 'warning' ? 'status-warning' : 'status-critical' );
							?>
							<tr>
								<td><?php echo esc_html( date_i18n( 'd/m/Y H:i:s', $check['timestamp'] ) ); ?></td>
								<td>
									<span class="health-status <?php echo esc_attr( $status_class ); ?>" style="font-size: 11px; padding: 2px 6px;">
										<?php echo esc_html( ucfirst( (string) ( $check['cdn_status'] ?? 'unknown' ) ) ); ?>
									</span>
								</td>
								<td>
									<strong><?php echo esc_html( $check['response_time'] ); ?>ms</strong>
								</td>
								<td>
									<span class="health-status <?php echo esc_attr( $health_class ); ?>" style="font-size: 11px; padding: 2px 6px;">
										<?php echo esc_html( ucfirst( (string) ( $check['overall_health'] ?? 'unknown' ) ) ); ?>
									</span>
								</td>
								<td>
									<?php
									if ( ! empty( $check['recommendations'] ) ) {
										echo '<ul style="margin: 0; padding-left: 16px; font-size: 11px;">';
										foreach ( $check['recommendations'] as $rec ) {
											echo '<li>' . esc_html( $rec ) . '</li>';
										}
										echo '</ul>';
									} else {
										echo '<span style="color: #46b450;">✓ ' . esc_html__( 'No issues', 'apollo-core' ) . '</span>';
									}
									?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<?php if ( count( $history ) > 20 ) : ?>
					<p class="description" style="margin-top: 10px;">
						<?php printf( esc_html__( 'Showing last 20 of %d total checks.', 'apollo-core' ), count( $history ) ); ?>
					</p>
				<?php endif; ?>
			</div>
			<?php endif; ?>

			<?php if ( $last_check ) : ?>
			<div class="apollo-cdn-last-check card" style="margin: 20px 0; padding: 20px;">
				<h2 style="margin-top: 0;"><?php _e( 'Last Health Check Details', 'apollo-core' ); ?></h2>
				<p><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $last_check['timestamp'] ) ); ?></p>

				<div class="health-status status-<?php echo esc_attr( $last_check['overall_health'] ); ?>">
					<?php echo esc_html( ucfirst( $last_check['overall_health'] ) ); ?>
				</div>

				<?php if ( ! empty( $last_check['recommendations'] ) ) : ?>
				<div class="health-recommendations">
					<h3><?php _e( 'Recommendations', 'apollo-core' ); ?></h3>
					<ul>
						<?php foreach ( $last_check['recommendations'] as $rec ) : ?>
						<li><?php echo esc_html( $rec ); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
				<?php endif; ?>
			</div>
			<?php endif; ?>

			<div id="apollo-cdn-results"></div>
		</div>

		<style>
		.apollo-cdn-stats { display: flex; gap: 20px; margin: 20px 0; }
		.stat-box { background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 4px; text-align: center; flex: 1; }
		.stat-value { font-size: 24px; font-weight: bold; color: #007cba; }
		.apollo-cdn-actions { margin: 20px 0; }
		.health-status { padding: 8px 12px; border-radius: 4px; color: white; font-weight: bold; display: inline-block; }
		.status-good { background: #46b450; }
		.status-warning { background: #ffb900; color: #23282d; }
		.status-critical { background: #dc3232; }
		.health-recommendations { margin-top: 20px; }
		</style>

		<!-- Include Chart.js from CDN -->
		<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

		<script>
		jQuery(document).ready(function($) {
			// Chart data from PHP.
			var chartLabels = <?php echo wp_json_encode( $chart_labels ); ?>;
			var chartResponseTimes = <?php echo wp_json_encode( $chart_response_times ); ?>;
			var chartStatuses = <?php echo wp_json_encode( $chart_statuses ); ?>;

			// Response time chart.
			if (chartLabels.length > 1 && document.getElementById('apollo-cdn-response-chart')) {
				var responseCtx = document.getElementById('apollo-cdn-response-chart').getContext('2d');
				new Chart(responseCtx, {
					type: 'line',
					data: {
						labels: chartLabels,
						datasets: [{
							label: '<?php _e( 'Response Time (ms)', 'apollo-core' ); ?>',
							data: chartResponseTimes,
							borderColor: '#007cba',
							backgroundColor: 'rgba(0, 124, 186, 0.1)',
							fill: true,
							tension: 0.3,
							pointRadius: 3,
							pointBackgroundColor: '#007cba'
						}]
					},
					options: {
						responsive: true,
						maintainAspectRatio: false,
						plugins: {
							title: {
								display: true,
								text: '<?php _e( 'CDN Response Time Over Time', 'apollo-core' ); ?>'
							},
							legend: {
								display: false
							}
						},
						scales: {
							y: {
								beginAtZero: true,
								title: {
									display: true,
									text: '<?php _e( 'Response Time (ms)', 'apollo-core' ); ?>'
								}
							},
							x: {
								title: {
									display: true,
									text: '<?php _e( 'Timestamp', 'apollo-core' ); ?>'
								}
							}
						}
					}
				});

				// Status chart (bar chart showing healthy vs unhealthy).
				var statusCtx = document.getElementById('apollo-cdn-status-chart').getContext('2d');
				new Chart(statusCtx, {
					type: 'bar',
					data: {
						labels: chartLabels,
						datasets: [{
							label: '<?php _e( 'Health Status', 'apollo-core' ); ?>',
							data: chartStatuses,
							backgroundColor: chartStatuses.map(function(s) {
								return s === 1 ? 'rgba(70, 180, 80, 0.8)' : 'rgba(220, 50, 50, 0.8)';
							}),
							borderColor: chartStatuses.map(function(s) {
								return s === 1 ? '#46b450' : '#dc3232';
							}),
							borderWidth: 1
						}]
					},
					options: {
						responsive: true,
						maintainAspectRatio: false,
						plugins: {
							title: {
								display: true,
								text: '<?php _e( 'CDN Status History (1 = Healthy, 0 = Unhealthy)', 'apollo-core' ); ?>'
							},
							legend: {
								display: false
							}
						},
						scales: {
							y: {
								beginAtZero: true,
								max: 1,
								ticks: {
									stepSize: 1,
									callback: function(value) {
										return value === 1 ? '<?php _e( 'Healthy', 'apollo-core' ); ?>' : '<?php _e( 'Unhealthy', 'apollo-core' ); ?>';
									}
								}
							}
						}
					}
				});
			}

			$('#apollo-cdn-health-check').on('click', function() {
				$(this).prop('disabled', true).text('<?php _e( 'Checking...', 'apollo-core' ); ?>');

				$.post(ajaxurl, {
					action: 'apollo_cdn_health_check',
					nonce: '<?php echo wp_create_nonce( 'apollo_cdn_monitor' ); ?>'
				}, function(response) {
					$('#apollo-cdn-results').html('<pre>' + JSON.stringify(response, null, 2) + '</pre>');
					$('#apollo-cdn-health-check').prop('disabled', false).text('<?php _e( 'Run Health Check', 'apollo-core' ); ?>');
					location.reload();
				});
			});

			$('#apollo-cdn-clear-history').on('click', function() {
				if (confirm('<?php _e( 'Are you sure you want to clear the health check history?', 'apollo-core' ); ?>')) {
					$.post(ajaxurl, {
						action: 'apollo_cdn_clear_history',
						nonce: '<?php echo wp_create_nonce( 'apollo_cdn_monitor' ); ?>'
					}, function() {
						location.reload();
					});
				}
			});
		});
		</script>
		<?php
	}

	/**
	 * Handle AJAX request to clear history
	 */
	public static function handle_clear_history(): void {
		check_ajax_referer( 'apollo_cdn_monitor', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Insufficient permissions', 'apollo-core' ) );
		}

		delete_option( 'apollo_cdn_health_history' );
		wp_send_json( array( 'success' => true ) );
	}

	/**
	 * Handle performance data from frontend
	 */
	public static function handle_performance_data(): void {
		check_ajax_referer( 'apollo_cdn_performance', 'nonce' );

		$data = json_decode( file_get_contents( 'php://input' ), true );

		if ( $data && isset( $data['data'] ) ) {
			$performance_data   = get_option( 'apollo_cdn_performance_data', array() );
			$performance_data[] = array(
				'timestamp' => current_time( 'timestamp' ),
				'data'      => $data['data'],
			);

			// Keep only last 50 performance reports.
			if ( count( $performance_data ) > 50 ) {
				$performance_data = array_slice( $performance_data, -50 );
			}

			update_option( 'apollo_cdn_performance_data', $performance_data );
		}

		wp_die();
	}
}

// Register AJAX handlers.
add_action( 'wp_ajax_apollo_cdn_health_check', array( 'Apollo_Core\CDN_Performance_Monitor', 'handle_health_check' ) );
add_action( 'wp_ajax_apollo_cdn_clear_history', array( 'Apollo_Core\CDN_Performance_Monitor', 'handle_clear_history' ) );
add_action( 'wp_ajax_nopriv_apollo_cdn_performance_data', array( 'Apollo_Core\CDN_Performance_Monitor', 'handle_performance_data' ) );

// Initialize CDN monitoring.
if ( class_exists( 'Apollo_Core\CDN_Performance_Monitor' ) ) {
	CDN_Performance_Monitor::init();
}
