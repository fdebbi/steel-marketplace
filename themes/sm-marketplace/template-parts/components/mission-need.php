<?php
/**
 * The detailed need of a study request (0.39.0): the answers of the client and
 * the indicative sketch drawn from them. Shown to the client and to the engineer.
 *
 * @var array $args { need: { part, rows: [ [label, value] ], drawing: array|null }, follow_url: string }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$need = (array) ( $args['need'] ?? array() );
if ( empty( $need['rows'] ) && empty( $args['follow_url'] ) ) {
	return; // Requests sent before 0.39.0: their description says it all.
}
$i18n = sm_study_sketch_i18n();
?>
<section class="sme-need-wrap">
	<?php if ( ! empty( $need['rows'] ) ) : ?>
	<h2 class="sm-panel__title"><?php esc_html_e( 'The need of the client', 'sm-marketplace' ); ?></h2>
	<div class="sme-need">
		<dl>
			<?php foreach ( $need['rows'] as $r ) : ?>
				<div><dt><?php echo esc_html( $r[0] ); ?></dt><dd><bdi dir="auto"><?php echo esc_html( $r[1] ); ?></bdi></dd></div>
			<?php endforeach; ?>
		</dl>
		<?php if ( ! empty( $need['drawing'] ) ) : ?>
			<div class="smx-sketch" data-smx-draw="<?php echo esc_attr( wp_json_encode( $need['drawing'] ) ); ?>" data-i18n="<?php echo esc_attr( wp_json_encode( $i18n ) ); ?>">
				<div class="smx-sketch__view">
					<p class="smx-sketch__title"><span data-smx-front-title><?php echo esc_html( $i18n['front'] ); ?></span><small><?php esc_html_e( 'Indicative sketch', 'sm-marketplace' ); ?></small></p>
					<div data-smx-front></div>
				</div>
				<div class="smx-sketch__view" data-smx-plan-box>
					<p class="smx-sketch__title"><span><?php echo esc_html( $i18n['plan'] ); ?></span><small data-smx-plan-note></small></p>
					<div data-smx-plan></div>
				</div>
				<div class="smx-figs" data-smx-figs></div>
				<p class="sm-help"><?php esc_html_e( 'Sketch drawn from the dimensions given by the client.', 'sm-marketplace' ); ?></p>
			</div>
		<?php endif; ?>
	</div>
	<?php endif; ?>
	<?php if ( ! empty( $args['follow_url'] ) ) : ?>
		<div class="sme-follow">
			<p><?php esc_html_e( 'Your study is delivered. Need an engineer to follow the fabrication and the erection?', 'sm-marketplace' ); ?></p>
			<a class="sm-btn sm-btn--primary" href="<?php echo esc_url( $args['follow_url'] ); ?>"><?php sm_icon( 'hard-hat' ); ?><?php esc_html_e( 'Ask for the follow-up of this project', 'sm-marketplace' ); ?></a>
		</div>
	<?php endif; ?>
</section>
