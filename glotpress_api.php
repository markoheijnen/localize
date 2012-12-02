<?php

class GlotPress_API {
	public static $cache = 360;

	public $url = 'http://translate.wordpress.org';
	public $project = 'wp';
	public $subproject = '';

	public function __construct( $url = false, $project = false, $subproject = false ) {
		if( $url )
			$this->url = $url;

		if( $project )
			$this->project = $project;

		if( $subproject )
			$this->subproject = $subproject;
	}


	public function versions() {
		$hash = "localize_" . $this->get_hash();
		$versions = get_transient( $hash );

		if( ! empty( $versions ) )
			return $versions;

		$data = $this->fetch();

		if( is_object( $data ) && isset( $data->sub_projects ) )
			set_transient( $hash, $data, self::$cache );

		return $data;
	}

	public function locales( $version ) {
		$hash = "localize_" . $this->get_hash( $version );
		$versions = get_transient( $hash );

		if( ! empty( $versions ) )
			return $versions;

		$data = $this->fetch( $version );

		if( is_object( $data ) && isset( $data->sub_projects ) )
			set_transient( $hash, $data, self::$cache );

		return $data;
	}

	/**
	 * get_locale_data( $locale, $version )
	 *
	 * Extracts the locale data from GlotPress api
	 * @param String $locale, the locale you want to get data about. Ex.: ru_RU
	 * @param String $version, the GlotPress version slug
	 * @return Mixed, an array of `name -> locale_slug` format
	 */
	public function get_locale( $locale, $version ) {
		$locales_info = $this->locales( $version );

		if( $locales_info ) {
			foreach( $locales_info->translation_sets as $t ) {
				if( strstr( $locale, $t->locale ) ) {
					return array( $t->name, $t->locale);
				}
			}
		}

		return false;
	}

	
	/**
	 * download_translation()
	 *
	 * Updates the translation file from the repo
	 * @return String, the name of the updated locale
	 */
	function download_translation( $version, $language, $format = 'mo' ) {
		$repo = $this->url . '/projects/%s/%s/%s/default/export-translations?format=%s';
		$languages_dir = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'languages' . DIRECTORY_SEPARATOR;

		$path = $languages_dir . $language . '.' . $format;
		
		if( ! is_dir( $languages_dir ) )
			@mkdir( $languages_dir, 0755, true );

		$locale = $this->get_locale( $language, $version );
		if( ! is_array( $locale ) )
			return;
		
		$file_uri = sprintf( $repo, $this->project, $version, $locale[1], $format );
		$tmp_path = download_url( $file_uri );

		if ( is_wp_error( $tmp_path ) ) {
			@unlink( $tmp_path );
			return false;
		}

		if( @copy( $tmp_path, $path ) )
			if( @unlink( $tmp_path ) )
				return $locale[0];
	}


	/**
	 * fetch_glotpress()
	 *
	 * Uses GlotPress api to get the repository details
	 * @return Mixed, decoded json from api, or false on failure
	 */
	private function fetch( $args = '' ) {
		global $wp_version;

		$api = $this->get_url( $args );
		$request = new WP_Http;
		
		$request_args = array(
			'timeout' => 30,
			'user-agent' => 'WordPress/' . $wp_version . '; Localize/' . LOCALIZE . '; ' . get_bloginfo( 'url' )
		);
		
		$response = $request->request( $api, $request_args );

		if( ! is_wp_error( $response ) )
			return json_decode( $response['body'] );
		else
			return false;
	}

	private function get_hash( $args = '' ) {
		return md5( $this->get_url( $args ) );
	}

	private function get_url( $args = '' ) {
		$url = $this->url . "/api/projects/" . $this->project . DIRECTORY_SEPARATOR . $args;

		if( $this->subproject )
			$url .= DIRECTORY_SEPARATOR . $this->subproject;

		return $url;
	}
}

function glotpess_get_local( $key ) {
	$codes = array(
		'aa' => '',
		'sq' => 'sq_AL',
		'am' => '',
		'ar' => '',
		'an' => '',
		'hy' => '',
		'az' => 'az_AZ',
		'az-tr' => 'az_TR',
		'eu' => '',
		'bn' => '',
		'bs' => '',
		'pt-br' => 'pt_BR',
		'br' => 'br_BR',
		'bg' => '',
		'mya' => '',
		'ca' => '',
		'bal' => '',
		'es-cl' => 'es_CL',
		'zh-cn' => '',
		'zh-tw' => '',
		'co' => '',
		'hr' => '',
		'cs' => '',
		'da' => '',
		'dv' => '',
		'nl' => 'nl_NL',
		'dz' => '',
		'en-ca' => '',
		'en-gb' => '',
		'eo' => '',
		'et' => '',
		'fo' => '',
		'fi' => '',
		'fr' => '',
		'fy' => '',
		'gl' => '',
		'ka' => '',
		'de' => '',
		'el' => '',
		'gu' => '',
		'haw' => '',
		'haz' => '',
		'he' => '',
		'hi' => '',
		'hu' => '',
		'is' => '',
		'id' => '',
		'ga' => '',
		'it' => '',
		'ja' => '',
		'jv' => '',
		'kn' => '',
		'kk' => '',
		'km' => '',
		'ky' => '',
		'ko' => '',
		'ckb' => '',
		'ku' => '',
		'lo' => '',
		'la' => '',
		'lv' => '',
		'li' => '',
		'lt' => '',
		'lb' => '',
		'mk' => '',
		'mg' => '',
		'ms' => '',
		'ml' => '',
		'mr' => '',
		'mn' => '',
		'me' => '',
		'ne' => '',
		'nb' => '',
		'nn' => '',
		'os' => '',
		'fa' => '',
		'fa-af' => '',
		'es-pe' => '',
		'pl' => '',
		'pt' => '',
		'pa' => '',
		'ro' => '',
		'ru' => '',
		'sa-in' => '',
		'srd' => '',
		'gd' => '',
		'sr' => '',
		'si' => '',
		'sk' => '',
		'sl' => '',
		'so' => '',
		'es' => '',
		'su' => '',
		'sw' => '',
		'sv' => '',
		'tl' => '',
		'tg' => '',
		'ta' => '',
		'ta-lk' => '',
		'te' => '',
		'th' => '',
		'tr' => '',
		'ug' => '',
		'uk' => '',
		'ur' => '',
		'uz' => '',
		'es-ve' => '',
		'vi' => '',
		'cy' => ''
	);

	if( isset( $codes[ $key ] ) )
		return $codes[ $key ];

	return $key;
}