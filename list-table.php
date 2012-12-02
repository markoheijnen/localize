<?php

if( ! class_exists('WP_List_Table') )
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

class Localize_List_Table extends WP_List_Table {
	private $locales;
	private $_views;
	private $current_view;

	function __construct() {
		global $status, $page;

		//Set parent defaults
		parent::__construct( array(
			'singular'  => 'locale',     //singular name of the listed records
			'plural'    => 'locales',    //plural name of the listed records
			'ajax'      => false        //does this table support ajax?
		) );
	}

	public function setData( $data ) {
		$this->locales = (array) $data;
	}

	function set_views( $data ) {
		$views = array();

		$this->current_view = key( $data );

		if( isset( $_GET['localize_key'], $data[ $_GET['localize_key'] ] ) )
			$this->current_view = esc_attr( $_GET['localize_key'] );

		$url = remove_query_arg( array( 'localize_key', 'locale', 'action' ) );
		foreach( $data as $key => $value ) {
			$class = '';

			if( $this->current_view == $key ) {
				$class = ' class="current"';
			}

			$views[ $key ] = '<a href="' . add_query_arg( 'localize_key', $key, $url ) . '"' . $class . '>' . $value['0'] . '</a>';
		}

		$this->_views = $views;
	}

	function get_views() {
		return $this->_views;
	}

	function get_current_view() {
		return $this->current_view;
	}

	function column_default( $item, $column_name ){
		switch( $column_name ) {
			case 'title':
				return $item->name;
			case 'locale':
				return glotpess_get_local( $item->locale );
			case 'description':
				$languages_dir = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'languages' . DIRECTORY_SEPARATOR;

				$path = $languages_dir . $item->locale . '.mo';
				
				if( is_file( $path ) )
					return __( 'Has translation', 'localize' );
				else
					return __( 'No translation', 'localize' );
		}
	}

	function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			/*$1%s*/ $this->_args['singular'],
			/*$2%s*/ $item->locale
		);
	}

	function get_columns() {
		$columns = array(
			'cb'          => '<input type="checkbox" />', //Render a checkbox instead of text
			'title'       => __('Languages'),
			'locale'      => __('Locale'),
			'description' => __('Installed')
		);

		return $columns;
	}

	function get_sortable_columns() {
		$sortable_columns = array(
			'title'  => array( 'name', true ),     //true means its already sorted
			'locale' => array( 'locale', false )
		);
		return $sortable_columns;
	}



	function column_title($item){
		$actions = array();

		if( is_super_admin() && 'wordpress' == $this->current_view )
			$actions['default'] = sprintf( '<a href="?page=%s&action=default&locale=%s">%s</a>', $_REQUEST['page'], glotpess_get_local( $item->locale ), __( 'Make default', 'localize' ) );

		//Build row actions
		/*
		if( $item['activated'] == true ) {
			$actions = array(
				'deactivate' => sprintf( '<a href="?page=%s&action=%s&ap_plugin=%s&ap_function=%s">Deactivate</a>', $_REQUEST['page'], 'deactivate', $item['plugin'], $item['function'] )
			);
		}
		else {
			$actions = array(
				'activate' => sprintf( '<a href="?page=%s&action=%s&ap_plugin=%s&ap_function=%s">Activate</a>', $_REQUEST['page'], 'activate', $item['plugin'], $item['function'] )
			);
		}*/

		//Return the title contents
		return sprintf('<strong>%1$s</strong> %2$s',
			/*$1%s*/ $item->name,
			/*$2%s*/ $this->row_actions($actions)
		);
	}

	function get_bulk_actions() {
		$actions = array(
			'download' => __( 'Download' ),
			'delete' => __( 'Delete' )
		);
		return $actions;
	}



/*
	public function single_row( $item ) {
		if( $item['activated'] == true ) {
			$row_class = ' class="active"';
		}
		else {
			$row_class = ' class="inactive"';
		}

		echo '<tr' . $row_class . '>';
		echo $this->single_row_columns( $item );
		echo '</tr>';
	}
*/


	function prepare_items() {
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array($columns, $hidden, $sortable);

		$data = $this->locales;

		function usort_reorder($a,$b){
			$orderby = (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : 'name'; //If no sort, default to title
			$order = (!empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'asc'; //If no order, default to asc
			$result = strcmp( $a->$orderby, $b->$orderby ); //Determine sort order
			return ($order==='asc') ? $result : -$result; //Send final sort direction to usort
		}
		usort($data, 'usort_reorder');

		$current_page = $this->get_pagenum();
		$total_items = count($data);
		$this->items = $data;
	}
}

?>