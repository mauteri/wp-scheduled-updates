<?php
/**
 * Plugin Name: WP Scheduled Updates
 * Plugin URI: https://github.com/mauteri/wp-scheduled-updates
 * Description: Schedules updates to live content.
 * Requires at least: 5.8
 * Requires PHP: 5.6
 * Version: 0.1
 * Author: Mike Auteri
 * Text Domain: wp-scheduled-updates
 *
 * @package better-shortcode-block
 */

use WP_Scheduled_Updates\Setup;

define( 'WP_SCHEDULED_UPDATES_URL', plugin_dir_url( __FILE__ ) );
define( 'WP_SCHEDULED_UPDATES_PATH', __DIR__ );

require_once 'classes/class-setup.php';

new Setup();
