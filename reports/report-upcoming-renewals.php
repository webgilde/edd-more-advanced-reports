<?php
/**
 * Register a report to show upcoming renewals (= active subscriptions) for a given period in the future.
 *
 * @param EDD\Reports\Data\Report_Registry $reports
 *
 * @return void
 */
add_action( 'edd_reports_init', 'edd_register_upcoming_renewals_report' );
function edd_register_upcoming_renewals_report( $reports ) {
	try {
		$options = EDD\Reports\get_dates_filter_options();
		$dates   = EDD\Reports\get_filter_value( 'dates' );
		$label   = $options[ $dates['range'] ];

		$reports->add_report( 'recurring_upcoming_renewals', array(
			'label'     => __( 'Upcoming Renewals', 'edd-recurring' ),
			'icon'      => 'calendar',
			'priority'  => 61,
			'endpoints' => array(
				'tiles' => array(
					'recurring_upcoming_renewals_number',
					'recurring_upcoming_renewals_net_earnings',
					'recurring_upcoming_renewals_tax',
					'recurring_upcoming_renewals_gross_earnings',
					'recurring_upcoming_renewals_retention_number',
					'recurring_upcoming_renewals_retention_net_earnings',
				),
				'charts' => array(
					'recurring_upcoming_renewals_days_chart'
				)
			)
		) );

		$reports->register_endpoint( 'recurring_upcoming_renewals_number', array(
			'label' => __( 'Upcoming renewals', 'edd-recurring' ),
			'views' => array(
				'tile' => array(
					'data_callback' => 'edd_recurring_upcoming_renewals_number_callback',
					'display_args'  => array(
						'comparison_label' => $label
					)
				)
			)
		) );

		$reports->register_endpoint( 'recurring_upcoming_renewals_net_earnings', array(
			'label' => __( 'Net Earnings', 'edd-recurring' ),
			'views' => array(
				'tile' => array(
					'data_callback' => function() {
						$net = edd_recurring_upcoming_renewals_gross_earnings_callback() - edd_recurring_upcoming_renewals_tax_callback();
						return edd_currency_filter( edd_format_amount( $net ) );
					},
					'display_args'  => array(
						'comparison_label' => $label
					)
				)
			)
		) );

		$reports->register_endpoint( 'recurring_upcoming_renewals_tax', array(
			'label' => __( 'Taxes', 'edd-recurring' ),
			'views' => array(
				'tile' => array(
					'data_callback' => function() {
						return edd_currency_filter( edd_format_amount( edd_recurring_upcoming_renewals_tax_callback() ) );
					},
					'display_args'  => array(
						'comparison_label' => $label
					)
				)
			)
		) );

		$reports->register_endpoint( 'recurring_upcoming_renewals_gross_earnings', array(
			'label' => __( 'Gross Earnings', 'edd-recurring' ),
			'views' => array(
				'tile' => array(
					'data_callback' => function() {
						return edd_currency_filter( edd_format_amount( edd_recurring_upcoming_renewals_gross_earnings_callback() ) );
					},
					'display_args'  => array(
						'comparison_label' => $label
					)
				)
			)
		) );

		$reports->register_endpoint( 'recurring_upcoming_renewals_retention_number', array(
			'label' => __( 'Renewal Retention', 'edd-recurring' ),
			'views' => array(
				'tile' => array(
					'data_callback' => function() {
						return floor( edd_recurring_upcoming_renewals_number_callback() / edd_recurring_all_subscriptions_number_callback() * 100 ) . ' %';
					},
					'display_args'  => array(
						'comparison_label' => $label
					)
				)
			)
		) );

		$reports->register_endpoint( 'recurring_upcoming_renewals_retention_net_earnings', array(
			'label' => __( 'Net Earnings Retention', 'edd-recurring' ),
			'views' => array(
				'tile' => array(
					'data_callback' => function() {
						return floor ( ( edd_recurring_upcoming_renewals_gross_earnings_callback() - edd_recurring_upcoming_renewals_tax_callback() ) / edd_recurring_all_subscriptions_net_earnings_callback() * 100 ) . ' %';
					},
					'display_args'  => array(
						'comparison_label' => $label
					)
				)
			)
		) );

		$reports->register_endpoint( 'recurring_upcoming_renewals_days_chart', array(
			'label' => __( 'Upcoming Renewals', 'edd-recurring' ),
			'views' => array(
				'chart' => array(
					'data_callback' => 'EDD_More_Advanced_Reports_Upcoming_Renewals_Chart::get_chart_data',
					'type'          => 'line',
					'options'       => array(
						'datasets' => array(
							'earnings' => array(
								'label'                => __( 'Earnings', 'easy-digital-downloads' ),
								'borderColor'          => 'rgba(24,126,244,0.75)',
								'backgroundColor'      => 'rgba(24,126,244,0.1)',
								'fill'                 => true,
								'borderWidth'          => 2,
								'type'                 => 'currency',
								'pointRadius'          => 4,
								'pointHoverRadius'     => 6,
								'pointBackgroundColor' => 'rgb(255,255,255)',
								'yAxisID'              => 'earnings-y',
							),
							'renewals' => array(
								'label'                => __( 'Renewals', 'edd-recurring' ),
								'borderColor'          => 'rgb(237,194,64)',
								'backgroundColor'      => 'rgba(237,194,64,0.2)',
								'fill'                 => true,
								'borderWidth'          => 2,
								'borderCapStyle'       => 'round',
								'borderJoinStyle'      => 'round',
								'pointRadius'          => 4,
								'pointHoverRadius'     => 6,
								'pointBackgroundColor' => 'rgb(255,255,255)',
								'yAxisID'              => 'renewals-y',
							),
						),
						'scales' => array(
							'yAxes' => array(
								array(
									'id'        => 'earnings-y',
									'type'      => 'linear',
									'display'   => true,
									'position'  => 'left',
									'ticks'     => array(
										'maxTicksLimit'  => 5,
										'formattingType' => 'format',
										'suggestedMin'   => 0,
										'beginAtZero'    => true,
										'precision'      => 0,
									),
									'gridLines' => array(
										'display' => true,
									),
								),
								array(
									'id'        => 'renewals-y',
									'type'      => 'linear',
									'position'  => 'right',
									'display'   => true,
									'ticks'     => array(
										'maxTicksLimit'  => 5,
										'formattingType' => 'integer',
										'suggestedMin'   => 0,
										'beginAtZero'    => true,
										'precision'      => 0,
									),
									'gridLines' => array(
										'display' => true,
										'color'   => 'rgba(0,0,0,0.03)',
									),
								),
							),
						)
					)
				)
			)
		) );

	} catch( \Exception $e ) {

	}
}

/**
 * Fetches the number of active due subscriptions
 *
 * @return int
 */
function edd_recurring_upcoming_renewals_number_callback() {
	if ( ! function_exists( '\\EDD\\Reports\\get_dates_filter' ) ) {
		return 0;
	}

	global $wpdb;

	$dates = EDD\Reports\get_dates_filter( 'objects' );

	$number = $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(status) FROM {$wpdb->prefix}edd_subscriptions
			WHERE status = 'active'
			AND expiration >= %s AND expiration <= %s",
		$dates['start']->copy()->format( 'mysql' ),
		$dates['end']->copy()->format( 'mysql' )
	) );

	return absint( $number );
}

/**
 * Fetches the number of all due subscriptions, including cancelled ones
 *
 * @return int
 */
function edd_recurring_all_subscriptions_number_callback() {
	if ( ! function_exists( '\\EDD\\Reports\\get_dates_filter' ) ) {
		return 0;
	}

	global $wpdb;

	$dates = EDD\Reports\get_dates_filter( 'objects' );

	$number = $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*) FROM {$wpdb->prefix}edd_subscriptions
			WHERE expiration >= %s AND expiration <= %s",
		$dates['start']->copy()->format( 'mysql' ),
		$dates['end']->copy()->format( 'mysql' )
	) );

	return absint( $number );
}

/**
 * Fetches the gross earnings of active due subscriptions
 *
 * @return int
 */
function edd_recurring_upcoming_renewals_gross_earnings_callback() {
	if ( ! function_exists( '\\EDD\\Reports\\get_dates_filter' ) ) {
		return 0;
	}

	global $wpdb;

	$dates  = EDD\Reports\get_dates_filter( 'objects' );
	$column = EDD\Reports\get_taxes_excluded_filter() ? 'recurring_amount - recurring_tax' : 'recurring_amount';

	$number = $wpdb->get_var( $wpdb->prepare(
		"SELECT SUM( {$column} ) FROM {$wpdb->prefix}edd_subscriptions
			WHERE status = 'active'
			AND expiration >= %s AND expiration <= %s",
		$dates['start']->copy()->format( 'mysql' ),
		$dates['end']->copy()->format( 'mysql' )
	) );

	return $number;
}

/**
 * Fetches taxes of active due subscriptions
 *
 * @return int
 */
function edd_recurring_upcoming_renewals_tax_callback() {
	if ( ! function_exists( '\\EDD\\Reports\\get_dates_filter' ) ) {
		return 0;
	}

	if ( EDD\Reports\get_taxes_excluded_filter() ) {
		return 0;
	}

	global $wpdb;

	$dates  = EDD\Reports\get_dates_filter( 'objects' );

	$number = $wpdb->get_var( $wpdb->prepare(
		"SELECT SUM( recurring_tax ) FROM {$wpdb->prefix}edd_subscriptions
			WHERE status = 'active'
			AND expiration >= %s AND expiration <= %s",
		$dates['start']->copy()->format( 'mysql' ),
		$dates['end']->copy()->format( 'mysql' )
	) );

	return $number;
}

/**
 * Fetches the total net earnings of all due subscriptions, including cancelled
 *
 * @return int
 */
function edd_recurring_all_subscriptions_net_earnings_callback() {
	if ( ! function_exists( '\\EDD\\Reports\\get_dates_filter' ) ) {
		return 0;
	}

	global $wpdb;

	$dates  = EDD\Reports\get_dates_filter( 'objects' );

	$number = $wpdb->get_var( $wpdb->prepare(
		"SELECT SUM( recurring_amount - recurring_tax ) FROM {$wpdb->prefix}edd_subscriptions
			WHERE expiration >= %s AND expiration <= %s",
		$dates['start']->copy()->format( 'mysql' ),
		$dates['end']->copy()->format( 'mysql' )
	) );

	return $number;
}