<?php
/**
 * Plugin Name: EDD – More Advanced Reports
 * Plugin URL: https://webgilde.com
 * Description: Custom reports for EDD 3.x created by webgilde
 * Version: 1.0
 * Author: Thomas Maier
 * Author URI: https://webgilde.com
 */

if ( is_admin() ) {
    // include ( 'includes/extend-report-periods.php' );
    include ( 'reports/charts/report-upcoming-renewals-chart.php' );
    include ( 'reports/report-upcoming-renewals.php' );
    include ( 'reports/charts/report-cancelled-subscriptions-chart.php' );
    include ( 'reports/report-cancelled-subscriptions.php' );
}
