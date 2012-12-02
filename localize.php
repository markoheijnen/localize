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

include 'glotpress_api.php';
include 'list-table.php';

class Localize {
	static private $list_table;
	static private $error_message;

	/**
	 * init()
	 * 
	 * Sets the hooks and other initialization stuff
	 */
	function init() {
		add_action( 'admin_menu', array( __CLASS__, 'page' ) );
		add_action( 'admin_init', array( __CLASS__, 'localization' ) );

		add_action( 'current_screen', array( __CLASS__, 'current_screen' ) );
	}

	function current_screen( $screen ) {
		if( 'settings_page_localize' == $screen->base ) {
			self::$list_table = new Localize_List_Table();

			$views = array(
				'wordpress' => array( 'WordPress', 'http://translate.wordpress.org', 'wp', false ),
				'wordpress-cc' => array( 'Continents-cities', 'http://translate.wordpress.org', 'wp', 'cc' ),
				'wordpress-admin' => array( 'WordPress Admin', 'http://translate.wordpress.org', 'wp', 'admin' )
			);

			if( is_multisite() ) {
				$views['wordpress-network'] = array( 'WordPress Network Admin ', 'http://translate.wordpress.org', 'wp', 'admin/network' );
			}

			self::$list_table->set_views( $views );

			$glotpress = new GlotPress_API(
				$views[ self::$list_table->get_current_view() ][1],
				$views[ self::$list_table->get_current_view() ][2],
				$views[ self::$list_table->get_current_view() ][3]
			);

			if(  is_super_admin() && ! empty( $_GET['locale'] ) ) {
				$locale = esc_attr( $_GET['locale'] );
				$glotpress->download_translation( 'dev', $locale );

				if( ! self::update_config( $locale ) )
					self::$error_message = __( "Sorry, the <code>wp-config.php</code> could not be updated...", 'localize' );
				else
					self::$error_message = sprintf( __( '%s localization updated!', 'localize' ), esc_attr( $_GET['locale'] ) );
			}

			$data = $glotpress->locales( 'dev' );
			if( isset( $data->translation_sets ) )
				self::$list_table->setData( $data->translation_sets );
		}
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
		$page = add_options_page(
			__( 'Localization', 'localize' ),
			__( 'Localization', 'localize' ),
			'administrator',
			'localize',
			array( __CLASS__, 'page_body' )
		);

		add_action( 'load-' . $page, array( __CLASS__, 'help_tab' ) );
	}

	/**
	 * help_tab()
	 * 
	 * Adds a help tab to the options page
	 */
	function help_tab() {
		$content  = '<p>' . __( 'This plugin will help you enable localization for your language on this WordPress installation.','localize' ) . '</p>';
		$content .= '<p>' . __( 'All you need to do is select the language code from the list below, and the version you want to use.','localize' ) . '</p>';
		$content .= '<p>' . __( 'The <strong>stable version</strong> will load the file from already published translations.','localize' ) . '</p>';
		$content .= '<p>' . __( 'The <strong>development version</strong> will try to download the file directly from ','localize' );
		$content .= '<a href="http://translate.wordpress.org/">GlotPress (translate.wordpress.org)</a>.</p>';


		$screen = get_current_screen();
		$screen->add_help_tab( array(
			'id'      => 'help-introduction',
			'title'   => __( 'Introduction','localize' ),
			'content' => $content,
		) );
	}

	/**
	 * page_body()
	 * 
	 * Callback to render the options page and handle it's form
	 */
	function page_body() {
		$vars = array();
		$vars['flash'] = self::$error_message;
		$vars['list_table'] = self::$list_table;
		self::render( 'settings', $vars );
	}

	/**
	 * get_versions()
	 * 
	 * Extracts the repository versions from GlotPress api
	 * @return Mixed, an array of `name -> slug` versions
	 */
	function get_versions() {
		$versions  = false;
		$repo_info = GlotPress_API::versions();

		if( $repo_info )
			foreach( $repo_info->sub_projects as $p )
				$versions[$p->name] = $p->slug;

		return $versions;
	}

	/**
	 * update_config()
	 *
	 * Updates the `wp-config.php` file using new locale
	 * @return Boolean, false on failre and true on success
	 */
	function update_config( $locale ) {
		$wp_config_path = ABSPATH . 'wp-config.php';
		$wpc_h = fopen( $wp_config_path, "r+" );

		$content = stream_get_contents( $wpc_h );
		if( ! $content && ! flock( $wpc_h, LOCK_EX ) )
			return false;

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
