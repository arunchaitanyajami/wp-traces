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

	public function __construct() {
		/**
		 * Hook it into WordPress.
		 */
		add_action( 'all', [ $this, 'wp_trace_action' ], 99999, 99 );
	}

	public function wp_trace_action(){
		/*
		 * Even though this plugin should never EVER be used in production, this is
		 * a safety net. You have to actually set the showTrace=1 flag in the query
		 * string for it to operate. If you don't it will still slow down your
		 * site, but it won't do anything.
		 */
		if ( ! isset( $_GET['debug_trace'] ) || (bool) $_GET['debug_trace'] !== true ) {
			return;
		}

		/*
		 * There are 2 other flags you can set to control what is output
		 */
		$show_args = ( isset( $_GET['debug_args'] ) ? (bool) $_GET['debug_args'] : false );
		$show_time = ( isset( $_GET['debug_time'] ) ? (bool) $_GET['debug_time'] : false );


		/*
		 * This is the main array we are using to hold the list of actions
		 */
		static $actions = [];


		/*
		 * Some actions are not going to be of interet to you. Add them into this
		 * array to exclude them. Remove the two default if you want to see them.
		 */
		$excludeActions = [ 'gettext', 'gettext_with_context' ];
		$thisAction     = current_filter();
		$thisArguments  = func_get_args();

		if ( ! in_array( $thisAction, $excludeActions ) ) {
			$actions[] = [
				'action'    => $thisAction,
				'time'      => microtime( true ),
				'arguments' => print_r( $thisArguments, true )
			];
		}


		/*
		 * Shutdown is the last action, process the list.
		 */
		if ( $thisAction === 'shutdown' ) {
			$this->wp_trace_debug_output( $actions, $show_args, $show_time );
		}

		return;
	}

	public function wp_trace_debug_output( $actions = [], $show_args = false, $show_time = false ) {
		/*
		  * Let's do a little formatting here.
		  * The class "debug" is so you can control the look and feel
		  */
		echo '<pre class="debug">';

		foreach ( $actions as $thisAction ) {
			echo "Action Name : ";

			/*
			 * if you want the timings, let's make sure everything is padded out properly.
			 */
			if ( $show_time ) {
				$timeParts = explode( '.', $thisAction['time'] );
				echo '(' . $timeParts[0] . '.' . str_pad( $timeParts[1], 4, '0' ) . ') ';
			}


			echo $thisAction['action'] . PHP_EOL;

			/*
			 * If you've requested the arguments, let's display them.
			 */
			if ( $show_args && count( $thisAction['arguments'] ) > 0 ) {
				echo "Args:" . PHP_EOL . print_r( $thisAction['arguments'], true );
				echo PHP_EOL;
			}
		}

		echo '</pre>';

		return;
	}
}

new AC_Wp_Traces();
