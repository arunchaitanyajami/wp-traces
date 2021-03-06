<?php
/*
Plugin Name: WP Traces
Version: 1.0
Description: A simple plugin to show you exactly what actions are being called when you run WordPress.
Author: Arun Chaitanya Jami
Author URI: https://github.com/arunchaitanyajami
Plugin URI: https://github.com/arunchaitanyajami/wp-traces
*/

class AC_Wp_Traces {

	public static $SiteWideAction;
	public static $curretBlog;

	public function __construct() {
		/**
		 * Hook it into WordPress.
		 */
		add_action( 'all', [ $this, 'wp_trace_action' ], 99999, 99 );
		self::$curretBlog = get_current_blog_id();
	}

	/**
	 * Wo track all actions.
	 */
	public function wp_trace_action(){
		/*
		 * Plugin should never EVER be used in production.
		 */
		$debug_trace = filter_input(INPUT_GET, 'debug_trace', FILTER_SANITIZE_NUMBER_INT );
		if ( empty( $debug_trace ) ) {
			return;
		}

		/*
		 * There are 2 other flags you can set to control what is output
		 */
		$show_args = filter_input( INPUT_GET, 'debug_args', FILTER_SANITIZE_NUMBER_INT );
		$show_time = filter_input( INPUT_GET, 'debug_time', FILTER_SANITIZE_NUMBER_INT );

		/*
		 * This is the main array we are using to hold the list of actions
		 */
		static $actions = [];

		/*
		 * Some actions are not going to be of interet to you. Add them into this
		 * array to exclude them. Remove the two default if you want to see them.
		 */
		$exclude_actions   = [ 'gettext', 'gettext_with_context' ];
		$current_action    = current_filter();
		$current_blog      = get_current_blog_id();
		$current_arguments = func_get_args();

		if ( ! in_array( $current_action, $exclude_actions ) ) {
			$actions[] = [
				'current_blog' => $current_blog,
				'action'       => $current_action,
				'time'         => microtime( true ),
				'current_time' => current_time( 'mysql' ),
				'arguments'    => print_r( $current_arguments, true )
			];
		}


		/*
		 * Shutdown is the last action, process the list.
		 */
		if ( $current_action === 'shutdown' ) {
			self::$SiteWideAction[ self::$curretBlog ] = $actions;
			$this->wp_trace_debug_output( $actions, $show_args, $show_time );
			$this->wp_trace_debug_option();
		}

		return;
	}


	/**
	 * Trace output at the end of the file.
	 *
	 * @param array $actions
	 * @param bool $show_args
	 * @param bool $show_time
	 */
	public function wp_trace_debug_output( $actions = [], $show_args = false, $show_time = false ) {
		/*
		  * Let's do a little formatting here.
		  * The class "debug" is so you can control the look and feel
		  */
		echo '<pre class="debug">';

		foreach ( $actions as $current_action ) {
			echo "Action Name : ";

			/*
			 * if you want the timings, let's make sure everything is padded out properly.
			 */
			if ( $show_time ) {
				$time_parts = explode( '.', $current_action['time'] );
				echo '(' . $time_parts[0] . '.' . str_pad( $time_parts[1], 4, '0' ) . ') ';
			}


			echo $current_action['action'] . PHP_EOL;

			/*
			 * If you've requested the arguments, let's display them.
			 */
			if ( $show_args && count( $current_action['arguments'] ) > 0 ) {
				echo "Args:" . PHP_EOL . print_r( $current_action['arguments'], true );
				echo PHP_EOL;
			}
		}

		echo '</pre>';

		return;
	}

	/**
	 * Add site option to network site ( If multi-site ).
	 */
	public function wp_trace_debug_option() {
		update_blog_option( 1, esc_attr( 'wp_trace_%s', self::$curretBlog, current_time( 'U' ) ), self::$SiteWideAction );
	}
}

/**
 * Plugin Activation Hook.
 */
function ac_wp_traces_activate_plugin() {
	new AC_Wp_Traces();
}
register_activation_hook( __FILE__, 'ac_wp_traces_activate_plugin' );
