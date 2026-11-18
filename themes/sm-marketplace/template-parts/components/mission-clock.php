<?php
/**
 * Countdown of a study (0.42.0): the engineer's 24 hours to answer, then the time
 * of his study; for the client, the delivery date of his quote. Green, orange in
 * the last hours (or days), red once over; clock.js updates it every minute.
 *
 * @var array $args {
 *     @type array  $clock   Clock from SM Engineering: kind (respond|study|delivery), state, start, due,
 *                           warn, days, left, short, pct, given, until, day (Y-m-d), units.
 *     @type string $variant full (banner) or pill (lists).
 * }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$c = $args['clock'] ?? null;
if ( empty( $c['kind'] ) ) {
	return;
}
$variant = ( $args['variant'] ?? 'full' ) === 'pill' ? 'pill' : 'full';
$kind    = $c['kind'];

// Words per state; {d} is the time left (or passed), filled again every minute.
if ( 'respond' === $kind ) {
	$words = array(
		/* translators: {d}: time left, e.g. "18 h 42 min". */
		'ok'   => array( __( '{d} left to answer', 'sm-marketplace' ), sprintf( /* translators: 1: date and hour, 2: date and hour. */ __( 'Offered on %1$s — answer expected before %2$s', 'sm-marketplace' ), $c['given'], $c['until'] ) ),
		/* translators: {d}: time left. */
		'warn' => array( __( 'Only {d} left to answer', 'sm-marketplace' ), __( 'Without an answer, the platform may give the study to another engineer.', 'sm-marketplace' ) ),
		/* translators: {d}: time passed. */
		'late' => array( __( 'Time to answer over by {d}', 'sm-marketplace' ), __( 'You can still accept until the platform gives the mission to another engineer.', 'sm-marketplace' ) ),
	);
	$pill  = array( 'ok' => '{s}', 'warn' => '{s}', 'late' => __( 'Answer: time over', 'sm-marketplace' ) );
	$icon  = 'clock';
} elseif ( 'study' === $kind ) {
	/* translators: %s: date and hour. */
	$until = sprintf( __( 'To hand in before %s', 'sm-marketplace' ), $c['until'] );
	$words = array(
		/* translators: {d}: time left. */
		'ok'   => array( __( '{d} left', 'sm-marketplace' ), $until ),
		'warn' => array( __( '{d} left', 'sm-marketplace' ), $until ),
		/* translators: {d}: time passed. */
		'late' => array( __( 'Late by {d}', 'sm-marketplace' ), sprintf( /* translators: %s: date and hour. */ __( 'The time ended on %s', 'sm-marketplace' ), $c['until'] ) ),
	);
	/* translators: {s}: time passed, short. */
	$pill  = array( 'ok' => '{s}', 'warn' => '{s}', 'late' => __( 'Late {s}', 'sm-marketplace' ) );
	$icon  = 'ruler';
} else {
	/* translators: %s: date. */
	$title = sprintf( __( 'Delivery planned on %s', 'sm-marketplace' ), sm_date( $c['day'] ) );
	/* translators: %d: number of days of the quote; {d}: time left. */
	$left  = sprintf( _n( '{d} left · time of your quote: %d day', '{d} left · time of your quote: %d days', (int) $c['days'], 'sm-marketplace' ), (int) $c['days'] );
	$words = array(
		'ok'   => array( $title, $left ),
		'warn' => array( $title, $left ),
		/* translators: {d}: time passed. */
		'late' => array( $title, __( 'The planned date is passed by {d}. The platform follows your study and keeps you informed.', 'sm-marketplace' ) ),
	);
	$pill  = array( 'ok' => '{s}', 'warn' => '{s}', 'late' => __( 'Late {s}', 'sm-marketplace' ) );
	$icon  = 'calendar';
}
$state = in_array( $c['state'], array( 'ok', 'warn', 'late' ), true ) ? $c['state'] : 'ok';
$fill  = static fn( $t ) => str_replace( array( '{d}', '{s}' ), array( $c['left'], $c['short'] ), $t );
$data  = array(
	'start' => $c['start'],
	'due'   => $c['due'],
	'warn'  => (int) $c['warn'],
	'units' => $c['units'],
	'words' => $words,
	'pill'  => $pill,
);
$attrs = 'data-sm-clock="' . esc_attr( wp_json_encode( $data ) ) . '" data-state="' . esc_attr( $state ) . '"';

if ( 'pill' === $variant ) : ?>
	<span class="sm-clock-pill is-<?php echo esc_attr( $state ); ?>" <?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput ?> title="<?php echo esc_attr( $fill( $words[ $state ][0] ) ); ?>"><?php sm_icon( 'late' === $state ? 'alert' : 'clock' ); ?><span data-sm-clock-pill><?php echo esc_html( $fill( $pill[ $state ] ) ); ?></span></span>
<?php else : ?>
	<div class="sm-clock is-<?php echo esc_attr( $state ); ?>" <?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput ?> role="status">
		<span class="sm-clock__ic"><?php sm_icon( 'ok' === $state ? $icon : 'alert' ); ?></span>
		<div class="sm-clock__body">
			<b data-sm-clock-title><?php echo esc_html( $fill( $words[ $state ][0] ) ); ?></b>
			<small data-sm-clock-note><?php echo esc_html( $fill( $words[ $state ][1] ) ); ?></small>
			<span class="sm-clock__bar" aria-hidden="true"><i data-sm-clock-bar style="inline-size:<?php echo (int) $c['pct']; ?>%"></i></span>
		</div>
	</div>
<?php endif; ?>
