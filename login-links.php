<?php
/**
 * Plugin Name: Login Links
 * Plugin URI: https://github.com/trvswgnr/login-links.git
 * Description: Generate unique login links for users.
 * Version: 1.2.1
 * Author: Travis Aaron Wagner
 * Author URI: https://travisaw.com
 * Text Domain: login-links
 *
 * @package login-links
 */

// prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once 'class-login-links.php';
new Login_Links();
