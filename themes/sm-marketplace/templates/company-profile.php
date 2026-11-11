<?php
/**
 * Template Name: SM — Company profile
 *
 * Public page of a company or a professional (?c=slug), 0.14.0 layout:
 * - header: logo, status (company / professional), badges "Account verified"
 *   (contact e-mail confirmed, level 1) and "Verified company / professional" (registration
 *   documents checked by the platform, level 2), name, activities;
 * - main column: presentation, completed projects, services (each with its own
 *   "Request a quote" button opening the request form with that service
 *   chosen), products for sale;
 * - side column: information, location (address + map), contact details,
 *   sharing on social networks.
 * Clients and partner engineers have no public page.
 *
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$company = sm_theme_company( sm_theme_route( 'c', sm_theme_core_active() ? '' : 'acier-hodna' ) );
get_header();

if ( ! $company ) {
	echo '<div class="sm-container sm-section">';
	sm_component( 'empty-state', array( 'icon' => 'company', 'title' => __( 'Company not found', 'sm-marketplace' ), 'action_label' => __( 'Company directory', 'sm-marketplace' ), 'action_url' => sm_page_url( 'companies' ) ) );
	echo '</div>';
	get_footer();
	return;
}

$class      = sm_account_class( $company );
$is_pro     = 'professional' === $class;
$level      = (int) $company['verification'];
$wilayas    = sm_theme_wilayas();
$activities = sm_activity_labels();
$acts       = array_map( static fn( $a ) => $activities[ $a ] ?? $a, (array) $company['activities'] );
$listings   = (array) ( sm_theme_listings( array( 'company' => $company['slug'], 'per_page' => 24 ) )['items'] ?? array() );
$services   = (array) sm_theme_services( array( 'company' => $company['slug'] ) );
$references = array_filter( (array) sm_theme_data( 'portfolio', array( 'company' => $company['slug'] ) ), 'is_array' );
$name       = sm_t( $company['name'] );
$place      = sm_t( $wilayas[ $company['wilaya'] ] ?? '' );
$url        = sm_page_url( 'company', array( 'c' => $company['slug'] ) );

// Services that can be requested from this page (real services of SM Services).
$real_services = array_values( array_filter( $services, static fn( $s ) => isset( $s['status'] ) ) );
$viewer        = $real_services ? (array) ( ( sm_theme_data( 'service', array( 'id' => $real_services[0]['id'] ) ) ?? array() )['viewer'] ?? array() ) : array();
$can_request   = ! empty( $viewer['can_request'] ) || ( ! empty( $viewer['open_request'] ) && count( $real_services ) > 1 );
$is_owner      = ! empty( $viewer['is_owner'] );

// Zone covered by the services.
$zone = '';
if ( $real_services ) {
	$all  = (bool) array_filter( $real_services, static fn( $s ) => ! empty( $s['all_algeria'] ) );
	$wcnt = count( array_unique( array_merge( ...array_map( static fn( $s ) => (array) ( $s['wilayas'] ?? array() ), $real_services ) ) ) );
	$zone = $all ? __( 'All of Algeria', 'sm-marketplace' ) : sprintf( /* translators: %d: number of wilayas. */ _n( '%d wilaya', '%d wilayas', max( 1, $wcnt ), 'sm-marketplace' ), max( 1, $wcnt ) );
}

$sections = array( 'about' => array( __( 'Presentation', 'sm-marketplace' ), 0 ) );
if ( $references ) {
	$sections['references'] = array( __( 'Completed projects', 'sm-marketplace' ), count( $references ) );
}
if ( $services ) {
	$sections['services'] = array( __( 'Services', 'sm-marketplace' ), count( $services ) );
}
if ( $listings ) {
	$sections['products'] = array( __( 'Products for sale', 'sm-marketplace' ), count( $listings ) );
}

$address = trim( implode( ', ', array_filter( array( (string) ( $company['address'] ?? '' ), (string) ( $company['commune'] ?? '' ), $place ) ) ) );
$phone   = (string) ( $company['phone'] ?? '' );
$shares  = array(
	'facebook' => array( 'Facebook', 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode( $url ) ),
	'whatsapp' => array( 'WhatsApp', 'https://wa.me/?text=' . rawurlencode( $name . ' — ' . $url ) ),
	'linkedin' => array( 'LinkedIn', 'https://www.linkedin.com/sharing/share-offsite/?url=' . rawurlencode( $url ) ),
	'x'        => array( 'X', 'https://x.com/intent/post?url=' . rawurlencode( $url ) . '&text=' . rawurlencode( $name ) ),
	'telegram' => array( 'Telegram', 'https://t.me/share/url?url=' . rawurlencode( $url ) . '&text=' . rawurlencode( $name ) ),
);
?>
<section class="smc-head">
	<div class="sm-container">
		<?php sm_breadcrumb( array( array( __( 'Company directory', 'sm-marketplace' ), sm_page_url( 'companies' ) ), array( $name, '' ) ) ); ?>
		<div class="smc-head__row">
			<div class="smc-head__logo">
				<?php if ( ! empty( $company['logo'] ) ) : ?>
					<img src="<?php echo esc_url( $company['logo'] ); ?>" alt="<?php echo esc_attr( sprintf( /* translators: %s: company name. */ __( 'Logo of %s', 'sm-marketplace' ), $name ) ); ?>" width="140" height="140">
				<?php else : ?>
					<span aria-hidden="true"><?php echo esc_html( $company['initials'] ?? 'SM' ); ?></span>
				<?php endif; ?>
			</div>
			<div class="smc-head__id">
				<ul class="smc-badges" role="list">
					<li class="smc-badge smc-badge--class"><?php sm_icon( $is_pro ? 'wrench' : 'company' ); ?><?php echo esc_html( sm_class_label( $class ) ); ?></li>
					<?php if ( $level >= 1 ) : ?>
						<li class="smc-badge smc-badge--account"><?php sm_icon( 'check' ); ?><?php esc_html_e( 'Account verified', 'sm-marketplace' ); ?></li>
					<?php endif; ?>
					<?php if ( $level >= 2 ) : ?>
						<li class="smc-badge smc-badge--quality"><?php sm_icon( 'verified' ); ?><?php echo esc_html( sm_verified_label( $class ) ); ?></li>
					<?php endif; ?>
				</ul>
				<h1 class="smc-head__name"><?php echo esc_html( $name ); ?></h1>
				<?php if ( $is_pro && ! empty( $company['title'] ) && sm_t( $company['title'] ) ) : ?><p class="smc-head__sub"><?php echo esc_html( sm_t( $company['title'] ) ); ?></p><?php endif; ?>
				<p class="smc-head__meta">
					<span><?php sm_icon( 'map-pin' ); ?><?php echo esc_html( $place ); ?></span>
					<span><?php sm_icon( 'calendar' ); ?><?php echo esc_html( sprintf( /* translators: %s: year. */ __( 'Member since %s', 'sm-marketplace' ), $company['since'] ) ); ?></span>
					<?php if ( ! empty( $company['experience'] ) ) : ?>
						<span><?php sm_icon( 'hard-hat' ); ?><?php echo esc_html( sprintf( /* translators: %d: years. */ _n( '%d year of experience', '%d years of experience', (int) $company['experience'], 'sm-marketplace' ), (int) $company['experience'] ) ); ?></span>
					<?php endif; ?>
				</p>
			</div>
		</div>
	</div>
</section>

<div class="sm-container smc-body">
	<?php sm_theme_notices( true ); ?>
	<div class="smc-layout">
		<div class="smc-main">
			<nav class="smc-tabs" aria-label="<?php esc_attr_e( 'Company sections', 'sm-marketplace' ); ?>">
				<?php $first = true; foreach ( $sections as $key => list( $label, $count ) ) : ?>
					<a href="#smc-<?php echo esc_attr( $key ); ?>"<?php echo $first ? ' aria-current="true"' : ''; ?>><?php echo esc_html( $label ); ?><?php if ( $count ) : ?><span><?php echo esc_html( (string) $count ); ?></span><?php endif; ?></a>
				<?php $first = false; endforeach; ?>
			</nav>

			<section id="smc-about" class="smc-card smc-about" aria-labelledby="smc-about-t">
				<h2 id="smc-about-t"><?php esc_html_e( 'Presentation', 'sm-marketplace' ); ?></h2>
				<?php if ( sm_t( $company['about'] ) ) : ?>
					<p class="sm-prewrap"><?php sm_e( $company['about'] ); ?></p>
				<?php else : ?>
					<p class="sm-muted"><?php esc_html_e( 'This company has not written its presentation yet.', 'sm-marketplace' ); ?></p>
				<?php endif; ?>
				<?php if ( $acts ) : ?>
					<div class="smc-types">
						<span class="screen-reader-text"><?php echo esc_html( $is_pro ? __( 'Trades', 'sm-marketplace' ) : __( 'Types of company', 'sm-marketplace' ) ); ?></span>
						<ul class="smc-types__list" role="list"><?php foreach ( $acts as $act ) : ?><li><?php echo esc_html( $act ); ?></li><?php endforeach; ?></ul>
					</div>
				<?php endif; ?>
				<?php if ( $zone ) : ?>
					<dl class="smc-facts"><div><dt><?php esc_html_e( 'Area covered', 'sm-marketplace' ); ?></dt><dd><?php echo esc_html( $zone ); ?></dd></div></dl>
				<?php endif; ?>
				<?php if ( $is_pro ) : ?>
					<?php $quals = array_filter( (array) ( $company['qualifications'] ?? array() ) ); ?>
					<?php $specs = array_filter( (array) ( $company['specialities'] ?? array() ) ); ?>
					<?php if ( $quals ) : ?>
						<h3><?php esc_html_e( 'Qualifications', 'sm-marketplace' ); ?></h3>
						<ul class="sm-checklist"><?php foreach ( $quals as $q ) : ?><li><?php sm_icon( 'verified' ); ?><?php sm_e( $q ); ?></li><?php endforeach; ?></ul>
					<?php endif; ?>
					<?php if ( $specs ) : ?>
						<h3><?php esc_html_e( 'Specialisations', 'sm-marketplace' ); ?></h3>
						<ul class="sm-tags"><?php foreach ( $specs as $sp ) : ?><li><?php sm_e( $sp ); ?></li><?php endforeach; ?></ul>
					<?php endif; ?>
				<?php endif; ?>
			</section>

			<?php if ( $references ) : ?>
				<section id="smc-references" class="smc-section" aria-labelledby="smc-ref-t">
					<div class="smc-section__head"><h2 id="smc-ref-t"><?php esc_html_e( 'Completed projects', 'sm-marketplace' ); ?></h2></div>
					<?php sm_component( 'portfolio-grid', array( 'items' => $references ) ); ?>
				</section>
			<?php endif; ?>

			<?php if ( $services ) : ?>
				<section id="smc-services" class="smc-section" aria-labelledby="smc-svc-t">
					<div class="smc-section__head">
						<div>
							<h2 id="smc-svc-t"><?php esc_html_e( 'Services offered', 'sm-marketplace' ); ?></h2>
							<p><?php esc_html_e( 'Choose a service to ask this company for a quote.', 'sm-marketplace' ); ?></p>
						</div>
					</div>
					<div class="smc-services">
						<?php foreach ( $services as $s ) : ?>
							<?php
							$real  = isset( $s['status'] );
							$s_url = $real ? sm_page_url( 'service', array( 'id' => $s['id'] ) ) : $url;
							?>
							<article class="smc-service">
								<a class="smc-service__media" href="<?php echo esc_url( $s_url ); ?>" tabindex="-1" aria-hidden="true">
									<img src="<?php echo esc_url( sm_image_url( $s['image'] ) ); ?>" alt="" loading="lazy" decoding="async" width="640" height="400">
								</a>
								<div class="smc-service__body">
									<h3><a href="<?php echo esc_url( $s_url ); ?>"><?php sm_e( $s['title'] ); ?></a></h3>
									<ul class="smc-service__tags" role="list"><?php foreach ( array_slice( (array) $s['tags'], 0, 4 ) as $tag ) : ?><li><?php sm_e( $tag ); ?></li><?php endforeach; ?></ul>
									<?php if ( $real ) : ?>
										<p class="smc-service__facts">
											<span><?php sm_icon( 'map-pin' ); ?><?php echo esc_html( $s['all_algeria'] ? __( 'All of Algeria', 'sm-marketplace' ) : sprintf( /* translators: %d: number of wilayas. */ _n( '%d wilaya served', '%d wilayas served', count( $s['wilayas'] ), 'sm-marketplace' ), count( $s['wilayas'] ) ) ); ?></span>
											<strong><?php echo wp_kses_post( sm_service_price( $s ) ); ?></strong>
										</p>
									<?php endif; ?>
									<div class="smc-service__actions">
										<?php if ( $real && ! $is_owner ) : ?>
											<button type="button" class="sm-btn sm-btn--accent" data-smc-open="smc-request" data-smc-service="<?php echo esc_attr( (string) $s['id'] ); ?>"><?php sm_icon( 'rfq' ); ?><?php esc_html_e( 'Request a quote', 'sm-marketplace' ); ?></button>
										<?php endif; ?>
										<a class="sm-btn sm-btn--ghost smc-service__more" href="<?php echo esc_url( $s_url ); ?>"><span class="screen-reader-text"><?php esc_html_e( 'See the service', 'sm-marketplace' ); ?></span><?php sm_icon( 'arrow-end' ); ?></a>
									</div>
								</div>
							</article>
						<?php endforeach; ?>
					</div>
				</section>
			<?php endif; ?>

			<?php if ( $listings ) : ?>
				<section id="smc-products" class="smc-section" aria-labelledby="smc-prod-t">
					<div class="smc-section__head">
						<div>
							<h2 id="smc-prod-t"><?php esc_html_e( 'Products for sale', 'sm-marketplace' ); ?></h2>
							<p><?php esc_html_e( 'Listings published by this company on the marketplace.', 'sm-marketplace' ); ?></p>
						</div>
					</div>
					<div class="sm-grid sm-grid--grid sm-grid--3">
						<?php foreach ( $listings as $item ) : ?><?php sm_component( 'card-listing', array( 'item' => $item ) ); ?><?php endforeach; ?>
					</div>
				</section>
			<?php endif; ?>
		</div>

		<aside class="smc-side" aria-label="<?php esc_attr_e( 'Company details', 'sm-marketplace' ); ?>">
			<section class="smc-card smc-location" aria-labelledby="smc-loc-t">
				<h2 id="smc-loc-t"><?php esc_html_e( 'Location', 'sm-marketplace' ); ?></h2>
				<p class="smc-address"><?php sm_icon( 'map-pin' ); ?><span dir="auto"><?php echo esc_html( $address ? $address : $place ); ?></span></p>
				<?php if ( ! empty( $company['location'] ) ) : ?>
					<?php sm_component( 'location-map', array( 'mode' => 'view', 'lat' => $company['location']['lat'], 'lng' => $company['location']['lng'], 'label' => $name ) ); ?>
				<?php endif; ?>
			</section>

			<section class="smc-card" aria-labelledby="smc-contact-t">
				<h2 id="smc-contact-t"><?php esc_html_e( 'Contact details', 'sm-marketplace' ); ?></h2>
				<ul class="smc-contact" role="list">
					<?php if ( $phone ) : ?>
						<li>
							<span class="smc-contact__icon"><?php sm_icon( 'phone' ); ?></span>
							<span class="smc-contact__text"><small><?php esc_html_e( 'Phone', 'sm-marketplace' ); ?></small>
								<a href="tel:<?php echo esc_attr( preg_replace( '/[^\d+]/', '', $phone ) ); ?>" data-smc-phone hidden><bdi dir="ltr"><?php echo esc_html( $phone ); ?></bdi></a>
								<strong data-smc-phone-mask><bdi dir="ltr"><?php echo esc_html( mb_substr( preg_replace( '/\s+/', '', $phone ), 0, 4 ) . ' •• •• ••' ); ?></bdi></strong>
							</span>
							<button type="button" class="smc-contact__reveal" data-smc-reveal><?php esc_html_e( 'Show', 'sm-marketplace' ); ?></button>
						</li>
					<?php endif; ?>
					<?php if ( ! empty( $company['email'] ) ) : ?>
						<li>
							<span class="smc-contact__icon"><?php sm_icon( 'mail' ); ?></span>
							<span class="smc-contact__text"><small><?php esc_html_e( 'E-mail', 'sm-marketplace' ); ?></small><a href="mailto:<?php echo esc_attr( $company['email'] ); ?>"><bdi dir="ltr"><?php echo esc_html( $company['email'] ); ?></bdi></a></span>
						</li>
					<?php endif; ?>
					<?php if ( ! empty( $company['website'] ) ) : ?>
						<li>
							<span class="smc-contact__icon"><?php sm_icon( 'globe' ); ?></span>
							<span class="smc-contact__text"><small><?php esc_html_e( 'Website', 'sm-marketplace' ); ?></small><a href="<?php echo esc_url( $company['website'] ); ?>" rel="noopener nofollow" target="_blank"><bdi dir="ltr"><?php echo esc_html( preg_replace( '#^https?://(www\.)?#', '', untrailingslashit( $company['website'] ) ) ); ?></bdi></a></span>
						</li>
					<?php endif; ?>
				</ul>
				<?php if ( ! $is_owner ) : ?>
					<div class="smc-contact__actions">
						<?php if ( $real_services ) : ?>
							<button type="button" class="sm-btn sm-btn--accent sm-btn--block" data-smc-open="smc-request"><?php sm_icon( 'rfq' ); ?><?php esc_html_e( 'Request a quote', 'sm-marketplace' ); ?></button>
						<?php endif; ?>
						<button type="button" class="sm-btn <?php echo $real_services ? 'sm-btn--ghost' : 'sm-btn--accent'; ?> sm-btn--block" data-smc-open="smc-message"><?php sm_icon( 'message' ); ?><?php esc_html_e( 'Send a message', 'sm-marketplace' ); ?></button>
					</div>
				<?php endif; ?>
			</section>

			<section class="smc-card smc-share" aria-labelledby="smc-share-t">
				<h2 id="smc-share-t"><?php esc_html_e( 'Share this page', 'sm-marketplace' ); ?></h2>
				<ul class="smc-share__list" role="list">
					<?php foreach ( $shares as $key => list( $label, $href ) ) : ?>
						<li><a class="smc-share__btn smc-share__btn--<?php echo esc_attr( $key ); ?>" href="<?php echo esc_url( $href ); ?>" target="_blank" rel="noopener" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: social network. */ __( 'Share on %s', 'sm-marketplace' ), $label ) ); ?>"><?php sm_icon( 'social-' . $key ); ?></a></li>
					<?php endforeach; ?>
					<li><button type="button" class="smc-share__btn smc-share__btn--copy" data-smc-copy="<?php echo esc_attr( $url ); ?>" data-smc-copied="<?php esc_attr_e( 'Link copied', 'sm-marketplace' ); ?>" aria-label="<?php esc_attr_e( 'Copy the link', 'sm-marketplace' ); ?>"><?php sm_icon( 'link' ); ?></button></li>
				</ul>
			</section>
		</aside>
	</div>
</div>

<?php if ( $real_services && ! $is_owner ) : ?>
	<dialog class="smc-dialog" id="smc-request" aria-labelledby="smc-request-t">
		<div class="smc-dialog__head">
			<div>
				<h2 id="smc-request-t"><?php esc_html_e( 'Request a quote', 'sm-marketplace' ); ?></h2>
				<p><?php echo esc_html( sprintf( /* translators: %s: company name. */ __( 'to %s · the answer arrives in your dashboard', 'sm-marketplace' ), $name ) ); ?></p>
			</div>
			<button type="button" class="smc-dialog__close" data-smc-close aria-label="<?php esc_attr_e( 'Close', 'sm-marketplace' ); ?>"><?php sm_icon( 'close' ); ?></button>
		</div>
		<div class="smc-dialog__body">
			<?php if ( $can_request ) : ?>
				<?php sm_theme_form_open( 'service_request', array( 'class' => 'sm-form', 'enctype' => 'multipart/form-data' ) ); ?>
					<div class="sm-field smc-chosen">
						<label for="smc-svc"><?php esc_html_e( 'Service', 'sm-marketplace' ); ?></label>
						<select id="smc-svc" name="service_id" data-smc-service-select>
							<?php foreach ( $real_services as $s ) : ?><option value="<?php echo esc_attr( (string) $s['id'] ); ?>"><?php echo esc_html( sm_t( $s['title'] ) ); ?></option><?php endforeach; ?>
						</select>
					</div>
					<div class="sm-field">
						<label for="smc-msg"><?php esc_html_e( 'Your project', 'sm-marketplace' ); ?></label>
						<textarea id="smc-msg" name="message" rows="5" dir="auto" maxlength="3000" required placeholder="<?php esc_attr_e( 'What must be made or installed: profiles, quantities, dimensions, site, finish…', 'sm-marketplace' ); ?>"></textarea>
					</div>
					<div class="sm-field sm-field--row">
						<div>
							<label for="smc-w"><?php esc_html_e( 'Wilaya of the project', 'sm-marketplace' ); ?></label>
							<select id="smc-w" name="wilaya" required>
								<option value=""><?php esc_html_e( 'Choose…', 'sm-marketplace' ); ?></option>
								<?php $acc = sm_theme_account(); foreach ( $wilayas as $c => $n ) : ?><option value="<?php echo esc_attr( $c ); ?>" <?php selected( (string) ( $acc['company']['wilaya'] ?? '' ), (string) $c ); ?>><?php echo esc_html( $c . ' — ' . sm_t( $n ) ); ?></option><?php endforeach; ?>
							</select>
						</div>
						<div>
							<label for="smc-date"><?php esc_html_e( 'Wanted by', 'sm-marketplace' ); ?> <small class="sm-optional"><?php esc_html_e( 'optional', 'sm-marketplace' ); ?></small></label>
							<input id="smc-date" name="needed_by" type="date" min="<?php echo esc_attr( gmdate( 'Y-m-d' ) ); ?>">
						</div>
					</div>
					<label class="sm-upload sm-upload--small">
						<input type="file" name="files[]" multiple accept=".pdf,image/jpeg,image/png,image/webp" data-sm-filename>
						<?php sm_icon( 'upload', 'sm-upload__icon' ); ?>
						<strong><?php esc_html_e( 'Add plans or photos', 'sm-marketplace' ); ?> <small class="sm-optional"><?php esc_html_e( 'optional', 'sm-marketplace' ); ?></small></strong>
						<small data-sm-filename-label><?php esc_html_e( 'PDF, JPG or PNG', 'sm-marketplace' ); ?></small>
					</label>
					<button class="sm-btn sm-btn--accent sm-btn--block sm-btn--lg" type="submit"><?php esc_html_e( 'Send the request', 'sm-marketplace' ); ?></button>
					<p class="sm-help"><?php esc_html_e( 'Your contact details are sent to the company with the request. It answers with a quote.', 'sm-marketplace' ); ?></p>
				</form>
			<?php else : ?>
				<p><?php echo esc_html( ! empty( $viewer['open_request'] ) ? __( 'You already sent a request for this service.', 'sm-marketplace' ) : (string) ( $viewer['request_block'] ?? '' ) ); ?></p>
				<?php if ( empty( $viewer['signed_in'] ) ) : ?>
					<div class="smq-actions">
						<a class="sm-btn sm-btn--primary" href="<?php echo esc_url( add_query_arg( 'redirect_to', rawurlencode( $url ), sm_page_url( 'login' ) ) ); ?>"><?php sm_icon( 'user' ); ?><?php esc_html_e( 'Sign in', 'sm-marketplace' ); ?></a>
						<a class="sm-btn sm-btn--ghost" href="<?php echo esc_url( sm_page_url( 'register' ) ); ?>"><?php esc_html_e( 'Create an account', 'sm-marketplace' ); ?></a>
					</div>
				<?php endif; ?>
			<?php endif; ?>
		</div>
	</dialog>
<?php endif; ?>

<?php if ( ! $is_owner ) : ?>
	<dialog class="smc-dialog" id="smc-message" aria-label="<?php esc_attr_e( 'Send a message', 'sm-marketplace' ); ?>">
		<div class="smc-dialog__head">
			<div><h2><?php esc_html_e( 'Send a message', 'sm-marketplace' ); ?></h2></div>
			<button type="button" class="smc-dialog__close" data-smc-close aria-label="<?php esc_attr_e( 'Close', 'sm-marketplace' ); ?>"><?php sm_icon( 'close' ); ?></button>
		</div>
		<div class="smc-dialog__body">
			<?php
			sm_component(
				'enquiry-form',
				array(
					'kind'    => $is_pro ? 'service' : 'listing',
					'module'  => 'company',
					'company' => $company['slug'],
					'subject' => __( 'Contact from the company page', 'sm-marketplace' ),
					'title'   => $is_pro ? __( 'Contact this professional', 'sm-marketplace' ) : __( 'Contact this company', 'sm-marketplace' ),
					'id'      => 'sm-contact',
				)
			);
			?>
		</div>
	</dialog>
<?php endif; ?>
<?php
get_footer();
