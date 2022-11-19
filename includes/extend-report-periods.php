<?php
/**
 * Add periods in EDD Reports
 *
 * @param array $date_options Date filter options.
 */
add_action( 'edd_report_date_options', function( $date_options ){
	$date_options[ 'this_full_month' ] = __( 'This Month' );
	$date_options[ 'next_full_month' ] = __( 'Next Month' );

	return $date_options;
} );