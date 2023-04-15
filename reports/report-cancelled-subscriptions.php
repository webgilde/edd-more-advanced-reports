<?php

namespace EDD_More_Advanced_Reports;

use Exception;
use function EDD\Reports\get_dates_filter_options;
use function EDD\Reports\get_dates_filter;
use function EDD\Reports\get_filter_value;

/**
 * Register a report to show cancelled subscriptions for a given period.
 *
 * @param EDD\Reports\Data\Report_Registry $reports
 *
 * @return void
 */
add_action('edd_reports_init', 'EDD_More_Advanced_Reports\edd_register_cancelled_subscriptions_report');
function edd_register_cancelled_subscriptions_report($reports)
{
    try {
        $options = get_dates_filter_options();
        $dates   = get_filter_value('dates');

        $label = $options[$dates['range']];

        $reports->add_report('cancelled_subscriptions', array(
            'label'     => __('Cancelled Subscriptions', 'edd-recurring'),
            'icon'      => 'calendar',
            'priority'  => 61,
            'endpoints' => array(
                'tiles'  => array(
                    'cancelled_subscriptions_by_date',
                ),
                'charts' => array(
                    'cancelled_subscriptions_days_chart'
                )
            )
        ));

        $reports->register_endpoint('cancelled_subscriptions_by_date', array(
            'label' => __('Total Cancelled Subscriptions', 'edd-recurring'),
            'views' => array(
                'tile' => array(
                    'data_callback' => function () {
                        return edd_total_cancelled_subscriptions_by_date();
                    },
                    'display_args'  => array(
                        'comparison_label' => $label
                    )
                )
            )
        ));

        $reports->register_endpoint('cancelled_subscriptions_days_chart', array(
            'label' => __('Cancelled Subscriptions', 'edd-recurring'),
            'views' => array(
                'chart' => array(
                    'data_callback' => 'EDD_More_Advanced_Reports_Cancelled_Subscriptions_Chart::get_chart_data',
                    'type'          => 'line',
                    'options'       => array(
                        'datasets' => array(
                            'subscriptions' => array(
                                'label'                => __('Cancelled Subscriptions', 'edd-recurring'),
                                'borderColor'          => 'rgb(237,194,64)',
                                'backgroundColor'      => 'rgba(237,194,64,0.2)',
                                'fill'                 => true,
                                'borderWidth'          => 2,
                                'borderCapStyle'       => 'round',
                                'borderJoinStyle'      => 'round',
                                'pointRadius'          => 4,
                                'pointHoverRadius'     => 6,
                                'pointBackgroundColor' => 'rgb(255,255,255)',
                                'yAxisID'              => 'subscriptions-y',
                            ),
                        ),
                        'scales'   => array(
                            'yAxes' => array(
                                array(
                                    'id'        => 'subscriptions-y',
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
        ));

    } catch (Exception $e) {

    }
}

/**
 * Fetches the number of total cancelled subscriptions
 *
 * @return int
 */
function edd_total_cancelled_subscriptions_by_date()
{
    if (!function_exists('\\EDD\\Reports\\get_dates_filter')) {
        return 0;
    }

    global $wpdb;

    $dates = get_dates_filter('objects');

    $start = $dates['start']->copy()->format('mysql');
    $end   = $dates['end']->copy()->format('mysql');

    $result = $wpdb->get_var(
        "SELECT COUNT(extracted_date)
FROM (
         SELECT id,
                STR_TO_DATE(REGEXP_SUBSTR( REGEXP_SUBSTR( notes, '.* Status changed from active to cancelled .*' ), '^[a-z]+ [0-9]+, [0-9]+' ), '%M %d, %Y') AS extracted_date 
         FROM  `{$wpdb->prefix}edd_subscriptions`
         WHERE  `notes` LIKE  '%Status changed from active to cancelled by%'
     ) AS subquery
WHERE extracted_date BETWEEN '" . $start . "' AND '" . $end . "'");

    return absint($result);
}