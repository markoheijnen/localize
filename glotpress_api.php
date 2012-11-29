<?php

class GlotPress_API {
	private static $url = 'http://translate.wordpress.org';
	private static $project = 'wp';

	public function __construct( $url = false, $project = false ) {
		if( $url )
			$this->url = $url;

		if( $project )
			$this->project = $project;
	}


	static public function versions() {
		return self::fetch();
	}

	static public function locale( $version ) {
		return self::fetch( $version );
	}


    /**
     * fetch_glotpress()
     *
     * Uses GlotPress api to get the repository details
     * @return Mixed, decoded json from api, or false on failure
     */
    private function fetch( $args = '' ) {
        global $wp_version;
        
        $api = self::$url . "/api/projects/" . self::$project . DIRECTORY_SEPARATOR;
        $request = new WP_Http;
        
        $request_args = array(
            'timeout' => 30,
            'user-agent' => 'WordPress/' . $wp_version . '; Localize/' . LOCALIZE . '; ' . get_bloginfo( 'url' )
        );
        
        $response = $request->request( $api . $args, $request_args);

        if( ! is_wp_error( $response ) )
            return json_decode( $response['body'] );
        else
            return false;
    }
}