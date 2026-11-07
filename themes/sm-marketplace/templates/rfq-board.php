<?php
/**
 * Template Name: SM — Requests for quotation
 *
 * Public RFQ board layout and the "new request" form (sm-rfq, Phase 7).
 *
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;
get_header();
$wilayas = sm_theme_wilayas();
$rfqs    = array(
	array( 't' => array( 'fr' => 'IPE 270 — 48 barres de 12 m, livraison chantier', 'ar' => 'IPE 270 — 48 قضيبا بطول 12 م، توصيل إلى الورشة' ), 'w' => '16', 'd' => 1, 'o' => 5, 'kg' => 21000 ),
	array( 't' => array( 'fr' => 'Hangar de stockage 25 × 15 m, neuf ou occasion', 'ar' => 'هنغار تخزين 25 × 15 م، جديد أو مستعمل' ), 'w' => '19', 'd' => 2, 'o' => 3, 'kg' => 0 ),
	array( 't' => array( 'fr' => 'Tubes carrés 80×80×4 — 120 barres', 'ar' => 'أنابيب مربعة 80×80×4 — 120 قضيبا' ), 'w' => '28', 'd' => 4, 'o' => 7, 'kg' => 0 ),
);
?>
<section class="sm-pagehead">
	<div class="sm-container">
		<?php sm_breadcrumb( array( array( __( 'Requests for quotation', 'sm-marketplace' ), '' ) ) ); ?>
		<div class="sm-pagehead__row">
			<div>
				<h1 class="sm-pagehead__title"><?php esc_html_e( 'Requests for quotation', 'sm-marketplace' ); ?></h1>
				<p class="sm-pagehead__intro"><?php esc_html_e( 'Buyers describe what they need; suppliers answer with a price per kg and a delivery time.', 'sm-marketplace' ); ?></p>
			</div>
			<a class="sm-btn sm-btn--accent" href="#sm-new-rfq"><?php sm_icon( 'plus' ); ?><?php esc_html_e( 'New request', 'sm-marketplace' ); ?></a>
		</div>
	</div>
</section>
<div class="sm-container sm-section sm-rfq">
	<div class="sm-rfq__list">
		<?php foreach ( $rfqs as $r ) : ?>
			<article class="sm-card sm-card--rfq">
				<div class="sm-card__body">
					<h2 class="sm-card__title"><a href="#"><?php sm_e( $r['t'] ); ?></a></h2>
					<p class="sm-card__facts-inline">
						<span><?php sm_icon( 'map-pin' ); ?><?php echo esc_html( sm_t( $wilayas[ $r['w'] ] ) ); ?></span>
						<span><?php sm_icon( 'calendar' ); ?><?php echo esc_html( sm_ago( gmdate( 'Y-m-d H:i:s', time() - $r['d'] * DAY_IN_SECONDS ) ) ); ?></span>
						<?php if ( $r['kg'] ) : ?><span><?php sm_icon( 'weight' ); ?><?php echo sm_kg( $r['kg'] ); // phpcs:ignore ?></span><?php endif; ?>
					</p>
				</div>
				<div class="sm-card--rfq__side">
					<span class="sm-count sm-count--lg"><?php echo esc_html( $r['o'] ); ?></span>
					<small><?php echo esc_html( _n( 'offer', 'offers', $r['o'], 'sm-marketplace' ) ); ?></small>
					<a class="sm-btn sm-btn--primary-outline" href="#"><?php esc_html_e( 'Make an offer', 'sm-marketplace' ); ?></a>
				</div>
			</article>
		<?php endforeach; ?>
	</div>

	<section class="sm-panel" id="sm-new-rfq">
		<h2 class="sm-panel__title"><?php esc_html_e( 'New request for quotation', 'sm-marketplace' ); ?></h2>
		<form class="sm-form" method="post" action="#" enctype="multipart/form-data" data-sm-demo-form>
			<div class="sm-field">
				<label for="q-cat"><?php esc_html_e( 'What do you need?', 'sm-marketplace' ); ?></label>
				<select id="q-cat" name="market"><?php foreach ( array_slice( sm_market_labels(), 0, 4, true ) as $k => $l ) : ?><option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $l ); ?></option><?php endforeach; ?></select>
			</div>
			<div class="sm-field sm-field--row">
				<div><label for="q-desig"><?php esc_html_e( 'Profile designation', 'sm-marketplace' ); ?></label><input id="q-desig" name="designation" list="sm-profiles-rfq" dir="ltr" placeholder="IPE 270"></div>
				<div><label for="q-qty"><?php esc_html_e( 'Quantity', 'sm-marketplace' ); ?></label><input id="q-qty" name="quantity" inputmode="numeric" dir="ltr"></div>
			</div>
			<datalist id="sm-profiles-rfq"><?php foreach ( array( 'IPE', 'HEA', 'HEB', 'UPN' ) as $f ) : ?><option value="<?php echo esc_attr( $f ); ?> "></option><?php endforeach; ?></datalist>
			<div class="sm-field sm-field--row">
				<div><label for="q-w"><?php esc_html_e( 'Delivery wilaya', 'sm-marketplace' ); ?></label><select id="q-w" name="wilaya"><?php foreach ( $wilayas as $c => $n ) : ?><option value="<?php echo esc_attr( $c ); ?>"><?php echo esc_html( sm_t( $n ) ); ?></option><?php endforeach; ?></select></div>
				<div><label for="q-date"><?php esc_html_e( 'Needed by', 'sm-marketplace' ); ?></label><input id="q-date" name="date" type="date"></div>
			</div>
			<div class="sm-field"><label for="q-desc"><?php esc_html_e( 'Technical description', 'sm-marketplace' ); ?></label><textarea id="q-desc" name="description" rows="4" dir="auto"></textarea></div>
			<label class="sm-upload sm-upload--small"><input type="file" name="files[]" multiple accept=".pdf,.dwg,.dxf,image/*"><?php sm_icon( 'upload', 'sm-upload__icon' ); ?><strong><?php esc_html_e( 'Attach drawings or a bill of materials', 'sm-marketplace' ); ?></strong></label>
			<button class="sm-btn sm-btn--accent sm-btn--block" type="submit"><?php esc_html_e( 'Publish my request', 'sm-marketplace' ); ?></button>
		</form>
	</section>
</div>
<?php
get_footer();
