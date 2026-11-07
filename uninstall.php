<?php
/**
 * Uninstall entrypoint.
 *
 * Intentionally preserves all data. Destructive cleanup requires an explicit
 * opt-in policy and a tested implementation in a future version.
 */
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}
