<?php
/**
 * Reports Chart for upcoming renewals (= active subscriptions) by date.
 *
 * @package   edd-more-advanced-reports
 * @copyright Copyright (c) 2022, Thomas Maier
 * @license   GPL2+
 *
 * Based on EDD_Recurring_Reports_Chart by Awesome Motives
 */

class EDD_More_Advanced_Reports_Upcoming_Renewals_Chart {

	/**
	 * Final array of graph data.
	 *
	 * @var array[]
	 */
	public $graph_data = array(
		'renewals'          => array(), // Renewal count
		'earnings'          => array(), // Renewal earnings
	);

	/**
	 * Date range for the query.
	 *
	 * @var array
	 */
	private $dates;

	/**
	 * True if results should use day by day, otherwise false.
	 *
	 * @var bool
	 */
	private $day_by_day;

	/**
	 * True if results should use hour by hour, otherwise false.
	 *
	 * @var bool
	 */
	private $hour_by_hour;

	/**
	 * Column name to query for amounts, depending on tax filter.
	 *
	 * @var string
	 */
	private $query_column;

	/**
	 * EDD_Recurring_Reports_Chart constructor.
	 *
	 * @param array $dates Date range for the query.
	 */
	public function __construct() {
		if ( function_exists( '\\EDD\\Reports\\get_dates_filter_day_by_day' ) ) {
			$this->dates        = EDD\Reports\get_dates_filter( 'objects' );
			$this->day_by_day   = EDD\Reports\get_dates_filter_day_by_day();
			$this->hour_by_hour = EDD\Reports\get_dates_filter_hour_by_hour();
			$this->query_column = EDD\Reports\get_taxes_excluded_filter() ? 'recurring_amount - recurring_tax' : 'recurring_amount';

			$this->query();
		}
	}

	/**
	 * Returns the data that makes up the renewals chart.
	 *
	 * @return array[]
	 */
	public static function get_chart_data() {
		$chart = new self();

		return $chart->graph_data;
	}

	/**
	 * Queries for graph data.
	 *
	 * @return void
	 */
	private function query() {
		global $wpdb;

		/**
		 * Query for active subscriptions
		 */
		$sale_results = $wpdb->get_results( $wpdb->prepare(
			"SELECT COUNT( id ) AS number, SUM({$this->query_column}) AS amount, expiration AS date
			FROM {$wpdb->prefix}edd_subscriptions
			WHERE status = 'active'
			AND expiration >= %s AND expiration <= %s
			GROUP BY DATE(expiration)
			ORDER BY DATE(expiration)",
			$this->dates['start']->copy()->format( 'mysql' ),
			$this->dates['end']->copy()->format( 'mysql' )
		) );

		try {
			// Initialise all arrays with timestamps and set values to 0.
			while ( strtotime( $this->dates['start']->copy()->format( 'mysql' ) ) <= strtotime( $this->dates['end']->copy()->format( 'mysql' ) ) ) {
				$timestamp = strtotime( $this->dates['start']->copy()->format( 'mysql' ) );

				// These are counts
				$this->graph_data[ 'renewals' ][ $timestamp ][0] = $timestamp;
				$this->graph_data[ 'renewals' ][ $timestamp ][1] = 0;

				// These are dollar earnings
				$this->graph_data[ 'earnings' ][ $timestamp ][0] = $timestamp;
				$this->graph_data[ 'earnings' ][ $timestamp ][1] = 0.00;

				$this->process_results( $sale_results, $timestamp, 'sale' );

				// Move the chart along to the next hour/day/month to get ready for the next loop.
				if ( $this->hour_by_hour ) {
					$this->dates['start']->addHour( 1 );
				} elseif ( $this->day_by_day ) {
					$this->dates['start']->addDays( 1 );
				} else {
					$this->dates['start']->addMonth( 1 );
				}
			}
		} catch ( \Exception $e ) {

		}

		foreach ( $this->graph_data as $data_key => $data_value ) {
			$this->graph_data[ $data_key ] = array_values( $data_value );
		}
	}

	/**
	 * Processes query results to add graph data.
	 *
	 * @param array  $results    Array of database objects.
	 * @param int    $timestamp  Unix timestamp.
	 * @param string $query_type Type of query that was performed.
	 *
	 * @return void
	 */
	private function process_results( $results, $timestamp, $query_type = 'sale' ) {
		// Loop through each date there were renewals, which we queried from the database.
		foreach ( $results as $result ) {

			try {
				$timezone         = new DateTimeZone( 'UTC' );
				$date_of_db_value = new DateTime( $result->date, $timezone );
				$date_on_chart    = new DateTime( $this->dates['start'], $timezone );
			} catch ( \Exception $e ) {
				continue;
			}

			// Add any renewals that happened during this hour.
			if ( $this->hour_by_hour ) {
				// If the date of this db value matches the date on this line graph/chart, set the y axis value for the chart to the number in the DB result.
				if ( $date_of_db_value->format( 'Y-m-d H' ) === $date_on_chart->format( 'Y-m-d H' ) ) {
					$this->add_graph_data( $result, $timestamp, $query_type );
				}
				// Add any renewals that happened during this day.
			} elseif ( $this->day_by_day ) {
				// If the date of this db value matches the date on this line graph/chart, set the y axis value for the chart to the number in the DB result.
				if ( $date_of_db_value->format( 'Y-m-d' ) === $date_on_chart->format( 'Y-m-d' ) ) {
					$this->add_graph_data( $result, $timestamp, $query_type );
				}
				// Add any renewals that happened during this month.
			} else {
				// If the date of this db value matches the date on this line graph/chart, set the y axis value for the chart to the number in the DB result.
				if ( $date_of_db_value->format( 'Y-m' ) === $date_on_chart->format( 'Y-m' ) ) {
					$this->add_graph_data( $result, $timestamp, $query_type );
				}
			}
		}
	}

	/**
	 * Adds data to the graph for a specific timestamp.
	 *
	 * @param object $result     Row from the database.
	 * @param int    $timestamp  Unix timestamp.
	 * @param string $query_type Type of query being performed.
	 *
	 * @return void
	 */
	public function add_graph_data( $result, $timestamp, $query_type ) {
		if ( 'sale' === $query_type ) {
			$this->graph_data['renewals'][ $timestamp ][1] += $result->number;
			$this->graph_data['earnings'][ $timestamp ][1] += abs( $result->amount );
		}
	}

}
