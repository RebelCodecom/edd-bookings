<?php

/**
 * AJAX handler class for the public module of the plugin.
 * 
 * @since 1.0.0
 * @version 1.0.0
 * @package EDD_Booking\Public
 */
class EDD_BK_Public_AJAX {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->define_hooks();
	}

	/**
	 * Registers the WordPress hooks into the loader.
	 */
	public function define_hooks() {
		$loader = EDD_Bookings::get_instance()->get_loader();
		// AJAX hook for retrieving the download availability
		$loader->add_action( 'wp_ajax_get_download_availability', $this, 'get_download_availability' );
		$loader->add_action( 'wp_ajax_nopriv_get_download_availability', $this, 'get_download_availability' );
		// AJAX hook for retrieving times for a selected date, for the timepicker on the front-end
		$loader->add_action( 'wp_ajax_get_times_for_date', $this, 'ajax_get_times_for_date' );
		$loader->add_action( 'wp_ajax_nopriv_get_times_for_date', $this, 'ajax_get_times_for_date' );
	}

	/**
	 * AJAX callback for retrieving the times for a specific date.
	 */
	public static function ajax_get_times_for_date() {
		if ( ! isset( $_POST['post_id'], $_POST['date'] ) ) {
			echo json_encode( array(
				'error' => __( 'A post ID and a valid date must be supplied!', EDD_Bookings::TEXT_DOMAIN )
			) );
			die();
		}
		$post_id = $_POST['post_id'];
		$date = $_POST['date'];

		// Get the download with this ID. Return an empty array if the ID doesn't match a download
		$download = edd_bk()->get_downloads_controller()->get( $post_id );
		if ( $download === NULL ) return array();

		// Parse the date string into a timestamp
		$date_parts = explode( '/', $date );
		$timestamp = mktime(0, 0, 0, $date_parts[0], $date_parts[1], $date_parts[2] );

		// Get the times
		$times = $download->getTimesForDate( $timestamp, edd_bk()->get_bookings_controller() );
		// Echo the JSON encoded times
		echo json_encode( $times );
		die();
	}

	/**
	 * Retrieves the availability for the given download.
	 *
	 * This method is triggered through a WP AJAX hook.
	 */
	public function get_download_availability() {
		if ( ! isset( $_POST['post_id'] ) ) {
			echo json_encode( array(
				'error' => __( 'No post ID as given.', EDD_Bookings::TEXT_DOMAIN )
			) );
		} else {
			$post_id = $_POST['post_id'];
			// Default date value
			$date = NULL;
			// Get the bookings controller
			$bookings_controller = edd_bk()->get_bookings_controller();
			// Get the download
			$download = edd_bk()->get_downloads_controller()->get( $post_id );
			// If the month and year are given, generate the date range
			if ( isset( $_POST['month'], $_POST['year'] ) ) {
				// Get POST data
				$month = intval( $_POST['month'] );
				$year = intval( $_POST['year'] );
				// Calculate the date range
				$start = mktime( 0, 0, 0, $month, 1, $year );
				$numdays = intval( date( 't', $start ) );
				$end = mktime( 23, 59, 0, $month, $numdays, $year );
				// Generate the range
				$date = array( $start, $end );
			}
			// Get the processed availability for the range
			$availability = $download->getProcessedAvailability( $bookings_controller, $date );
			echo json_encode( $availability );
		}
		die();
	}

}