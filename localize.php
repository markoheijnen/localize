<?php
/*
Plugin Name: Localize WordPress
Plugin URI: https://github.com/stas/localize
Description: Easily switch to any localization from GlotPress
Version: 0.4
Author: Stas Sușcov
Author URI: http://stas.nerd.ro/
*/

/*  Copyright 2011  Stas Sușcov <stas@nerd.ro>

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

define( 'LOCALIZE', '0.4' );
define( 'LOCALIZE_CACHE', '360' );

include 'glotpress_api.php';
include 'list-table.php';

class Localize {
	/**
	 * init()
	 * 
	 * Sets the hooks and other initialization stuff
	 */
	function init() {
		add_action( 'admin_menu', array( __CLASS__, 'page' ) );
		add_action( 'init', array( __CLASS__, 'localization' ) );
	}

	/**
	 * localization()
	 * 
	 * i18n
	 */
	function localization() {
		load_plugin_textdomain( 'localize', false, basename( dirname( __FILE__ ) ) . '/languages' );
	}

	/**
	 * page()
	 * 
	 * Adds the options page to existing menu
	 */
	function page() {
		add_options_page(
			__( 'Localization', 'localize' ),
			__( 'Localization', 'localize' ),
			'administrator',
			'localize',
			array( __CLASS__, 'page_body' )
		);
	}

	/**
	 * page()
	 * 
	 * Callback to render the options page and handle it's form
	 */
	function page_body() {
		$flash = null;

		if( isset( $_POST['localize_nonce'] ) && wp_verify_nonce( $_POST['localize_nonce'], 'localize' ) ) {
			$lang = null;
			$lang_version = null;
			$locale = null;

			if( isset( $_POST['lang'] ) && !empty( $_POST['lang'] ) )
				$lang = sanitize_text_field( $_POST['lang'] );

			if( isset( $_POST['lang_version'] ) && !empty( $_POST['lang_version'] ) )
				$lang_version = sanitize_text_field( $_POST['lang_version'] );

			if( $lang && strstr( $lang, '_' ) )
				update_option( 'localize_lang', $lang );

			if( $lang_version )
				update_option( 'localize_lang_version', $lang_version );

			if( !self::update_config() )
				$flash = __( "Sorry, the <code>wp-config.php</code> could not be updated...", 'localize' );

			if( $lang != 'en_US' )
				$locale = self::update_mo();
			else
				$locale = "English";

			if( !$locale )
				$flash = __( 'There was an error downloading the file!','localize' );
			else
				$flash = sprintf( __( '%s localization updated! Please reload this page...', 'localize' ), $locale );
		}

		$vars = self::get_locale();
		$vars['versions'] = self::get_versions();
		$vars['flash'] = $flash;

		$vars['list_table'] = new Localize_List_Table();

		$data = GlotPress_API::locales( 'dev' );

		if( isset( $data->translation_sets ) )
			$vars['list_table']->setData( $data->translation_sets );

		self::render( 'settings', $vars );
	}
	
	/**
	 * get_locale()
	 *
	 * Fetches the current options for custom locale
	 * @return Mixed, an array of options as keys
	 */
	function get_locale() {
		return array(
			'lang' => get_option( 'localize_lang', get_locale() ),
			'lang_version' => get_option( 'localize_lang_version', 'stable' )
		);
	}

	/**
	 * update_mo()
	 *
	 * Updates the po file from WordPress.org GlotPress repo
	 * @return String, the name of the updated locale
	 */
	function update_mo() {
		$settings = self::get_locale();	 
		$versions = self::get_versions();

		if( ! is_array( $versions ) )
			return;

		if( ! in_array( $settings['lang_version'], $versions ) )
			return;

		return GlotPress_API::download_translation( $settings['lang_version'], $settings['lang'] );
	}
	
	/**
	 * get_versions()
	 *
	 * Extracts the repository versions from GlotPress api
	 * @return Mixed, an array of `name -> slug` versions
	 */
	function get_versions() {
		$versions = get_transient( "localize_versions" );

		if( !empty( $versions ) )
			return $versions;

		$repo_info = GlotPress_API::versions();
		if( is_object( $repo_info ) && isset( $repo_info->sub_projects ) )
			foreach( $repo_info->sub_projects as $p )
				$versions[$p->name] = $p->slug;

		set_transient( "localize_versions", $versions, LOCALIZE_CACHE );
		return $versions;
	}

	/**
	 * update_config()
	 *
	 * Updates the `wp-config.php` file using new locale
	 * @return Boolean, false on failre and true on success
	 */
	function update_config() {
		$wp_config_path = ABSPATH . 'wp-config.php';
		$wpc_h = fopen( $wp_config_path, "r+" );

		$content = stream_get_contents( $wpc_h );
		if( ! $content && ! flock( $wpc_h, LOCK_EX ) )
			return false;

		$settings = self::get_locale();
		$locale = $settings['lang'];

		$source = "/define(.*)WPLANG(.*)\'(.*)\'(.*);(.*)/";
		$target = "define ('WPLANG', '$locale'); // Updated by `Localize` plugin";

		$content = preg_replace( $source, $target, $content );

		rewind( $wpc_h );
		if( ! @fwrite( $wpc_h, $content ) )
			return false;
		flock( $wpc_h, LOCK_UN );

		return true;
	}
	
	/**
	 * render( $name, $vars = null, $echo = true )
	 *
	 * Helper to load and render templates easily
	 * @param String $name, the name of the template
	 * @param Mixed $vars, some variables you want to pass to the template
	 * @param Boolean $echo, to echo the results or return as data
	 * @return String $data, the resulted data if $echo is `false`
	 */
	function render( $name, $vars = null, $echo = true ) {
		ob_start();
		if( !empty( $vars ) )
			extract( $vars );

		include dirname( __FILE__ ) . '/templates/' . $name . '.php';

		$data = ob_get_clean();

		if( $echo )
			echo $data;
		else
			return $data;
	}
}

Localize::init();
