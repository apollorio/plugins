<?php
/**
 * Apollo Core - Auth Scripts Inline (DEPRECATED)
 *
 * @package Apollo_Core
 * @license GPL-2.0-or-later
 * @deprecated 3.1.0 Use wp_enqueue_script + wp_localize_script in auth-routes.php
 *
 * Copyright (c) 2026 Apollo
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 */

if ( ! defined( 'ABSPATH' ) ) {
exit;
}

/**
 * @deprecated 3.1.0 This file is deprecated.
 * Use wp_enqueue_script + wp_localize_script in auth-routes.php instead.
 *
 * The inline script functionality has been moved to properly enqueued scripts.
 * This file is kept for backward compatibility but does nothing.
 */
_doing_it_wrong(
__FILE__,
esc_html__( 'auth-scripts-inline.php is deprecated. Use wp_enqueue_script in auth-routes.php instead.', 'apollo-core' ),
'3.1.0'
);