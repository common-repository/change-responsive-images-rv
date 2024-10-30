<?php
/**
 * @package rvCRI\ChangeResponsiveImages
 */

/**
 * Plugin Name: Change Responsive Images
 * Version: 1.1.3
 * Description: Edit the images that wordpress create automatically.
 * Author: Richard Venancio
 * Author URI: https://packsystem.com.br/
 * Text Domain: packsystem
 * Domain Path: /languages/
 * License: GPL v3
 */

/**
 * Change Responsive Images Plugin
 * Copyright (C) 2008-2016, Yoast BV - support@yoast.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

if (!function_exists('add_action') || !function_exists('add_filter')){
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	die();
}

define('WPCRI_VERSION', '1.0');

if ( ! defined( 'WPCRI_DIR' ) ) {
	define('WPCRI_DIR', plugin_dir_path( __FILE__ ));
}

require_once( WPCRI_DIR . 'ChangeResponsiveImages.class.php' );

function get_attachment_picture( $attachment_id, $size = 'thumbnail', $attr = '' ){
	return apply_filters( 'get_attachment_picture', '', $attachment_id, $size, $attr);
}