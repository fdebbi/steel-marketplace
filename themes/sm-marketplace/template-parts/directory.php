<?php
/**
 * Directory of companies or of professionals (0.15.0). Filters (GET): q, wilaya,
 * activity (type of company / trade), verified, sort (featured|newest|name), pg.
 *
 * @var array $args { class: company|professional }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$class    = 'professional' === ( $args['class'] ?? '' ) ? 'professional' : 'company';
$is_pro   = 'professional' === $class;
$page_key = $is_pro ? 'professionals' : 'companies';
$base     = sm_page_url( $page_key );
$types    = sm_account_types();
$acts     = (array) ( $types['classes'][ $class ]['activities'] ?? array() );
$meta     = (array) ( $types['meta'] ?? array() );
$wilayas  = sm_theme_wilayas();
$q        = mb_substr( (string) sm_theme_query_arg( 'q' ), 0, 80 );
$wilaya   = preg_replace( '/[^0-9]/', '', (string) sm_theme_query_arg( 'wilaya' ) );
$activity = sanitize_key( (string) sm_theme_query_arg( 'activity' ) );
$activity = isset( $acts[ $activity ] ) || isset( $meta[ $activity ] ) ? $activity : '';
$verified = '1' === sm_theme_query_arg( 'verified' );
$sort     = in_array( sm_theme_query_arg( 'sort' ), array( 'newest', 'name' ), true ) ? sm_theme_query_arg( 'sort' ) : 'featured';
$page_num = max( 1, (int) sm_theme_query_arg( 'pg', 1 ) );
$result   = (array) sm_theme_data( 'directory', array( 'class' => $class, 'activity' => $activity, 'wilaya' => $wilaya, 'search' => $q, 'verified' => $verified, 'orderby' => $sort, 'page' => $page_num, 'per_page' => 24 ) );
$result   = wp_parse_args( $result, array( 'items' => array(), 'total' => 0, 'pages' => 0 ) );
$filters  = array_filter( array( 'q' => $q, 'wilaya' => $wilaya, 'activity' => $activity, 'verified' => $verified ? '1' : '', 'sort' => 'featured' === $sort ? '' : $sort ) );
$lang     = sm_theme_current_language();
$sel      = $activity ? ( $meta[ $activity ] ?? null ) : null;
?>
<section class="smd-head">
	<div class="sm-container smd-head__inner">
		<?php sm_breadcrumb( array( array( $is_pro ? __( 'Professionals directory', 'sm-marketplace' ) : __( 'Company directory', 'sm-marketplace' ), '' ) ) ); ?>
		<h1><?php echo esc_html( $is_pro ? __( 'Professionals directory', 'sm-marketplace' ) : __( 'Company directory', 'sm-marketplace' ) ); ?></h1>
		<p><?php echo esc_html( $is_pro ? __( 'Welders, steel erectors, boilermakers and other professionals who buy, sell and offer their services.', 'sm-marketplace' ) : __( 'Manufacturers, distributors, recyclers and steel construction companies registered on the platform.', 'sm-marketplace' ) ); ?></p>
		<form class="smd-search" role="search" method="get" action="<?php echo esc_url( $base ); ?>">
			<?php if ( 'ar' === $lang ) : ?><input type="hidden" name="lang" value="ar"><?php endif; ?>
			<?php if ( $activity ) : ?><input type="hidden" name="activity" value="<?php echo esc_attr( $activity ); ?>"><?php endif; ?>
			<?php if ( $verified ) : ?><input type="hidden" name="verified" value="1"><?php endif; ?>
			<div class="smd-search__field smd-search__field--q">
				<label for="smd-q"><?php echo esc_html( $is_pro ? __( 'Name or trade', 'sm-marketplace' ) : __( 'Company name', 'sm-marketplace' ) ); ?></label>
				<input id="smd-q" name="q" type="search" value="<?php echo esc_attr( $q ); ?>" dir="auto">
			</div>
			<div class="smd-search__field">
				<label for="smd-w"><?php esc_html_e( 'Wilaya', 'sm-marketplace' ); ?></label>
				<select id="smd-w" name="wilaya">
					<option value=""><?php esc_html_e( 'All wilayas', 'sm-marketplace' ); ?></option>
					<?php foreach ( $wilayas as $code => $name ) : ?><option value="<?php echo esc_attr( $code ); ?>" <?php selected( $wilaya, (string) $code ); ?>><?php echo esc_html( $code . ' — ' . sm_t( $name ) ); ?></option><?php endforeach; ?>
				</select>
			</div>
			<button class="sm-btn sm-btn--accent sm-btn--lg" type="submit"><?php sm_icon( 'search' ); ?><?php esc_html_e( 'Search', 'sm-marketplace' ); ?></button>
		</form>
	</div>
</section>

<div class="sm-container sm-section smd-layout">
	<aside class="smd-filters" aria-label="<?php esc_attr_e( 'Filters', 'sm-marketplace' ); ?>">
		<h2><?php echo esc_html( $is_pro ? __( 'Trade', 'sm-marketplace' ) : __( 'Type of company', 'sm-marketplace' ) ); ?></h2>
		<ul class="smd-pills">
			<li><a href="<?php echo esc_url( add_query_arg( array_diff_key( $filters, array( 'activity' => 1 ) ), $base ) ); ?>" <?php echo $activity ? '' : 'aria-current="true"'; ?>><?php esc_html_e( 'All', 'sm-marketplace' ); ?></a></li>
			<?php foreach ( $acts as $code => $label ) : ?>
				<li><a href="<?php echo esc_url( add_query_arg( array( 'activity' => $code ) + $filters, $base ) ); ?>" <?php echo $code === $activity ? 'aria-current="true"' : ''; ?>><?php echo esc_html( $label ); ?></a></li>
			<?php endforeach; ?>
		</ul>
		<h2><?php esc_html_e( 'Verification', 'sm-marketplace' ); ?></h2>
		<a class="smd-toggle" href="<?php echo esc_url( add_query_arg( $verified ? array_diff_key( $filters, array( 'verified' => 1 ) ) : array( 'verified' => '1' ) + $filters, $base ) ); ?>" aria-pressed="<?php echo $verified ? 'true' : 'false'; ?>"><span class="smd-toggle__box"><?php sm_icon( 'check' ); ?></span><?php echo esc_html( $is_pro ? __( 'Verified professionals only', 'sm-marketplace' ) : __( 'Verified companies only', 'sm-marketplace' ) ); ?></a>
		<p class="smd-filters__other"><a href="<?php echo esc_url( sm_page_url( $is_pro ? 'companies' : 'professionals' ) ); ?>"><?php echo esc_html( $is_pro ? __( 'See the company directory', 'sm-marketplace' ) : __( 'See the professionals directory', 'sm-marketplace' ) ); ?></a></p>
	</aside>

	<div class="smd-main">
		<?php if ( $sel && ( $sel['desc'] || $sel['image'] ) ) : ?>
			<div class="smd-type">
				<?php if ( $sel['image'] ) : ?><img src="<?php echo esc_url( $sel['image'] ); ?>" alt="" loading="lazy"><?php endif; ?>
				<div><h2><?php echo esc_html( $sel['label'] ); ?></h2><?php if ( $sel['desc'] ) : ?><p><?php echo esc_html( $sel['desc'] ); ?></p><?php endif; ?></div>
			</div>
		<?php endif; ?>
		<div class="smd-bar">
			<p><?php echo esc_html( sprintf( /* translators: %d: number of accounts. */ $is_pro ? _n( '%d professional', '%d professionals', $result['total'], 'sm-marketplace' ) : _n( '%d company', '%d companies', $result['total'], 'sm-marketplace' ), $result['total'] ) ); ?></p>
			<form method="get" action="<?php echo esc_url( $base ); ?>" class="smd-sort">
				<?php foreach ( array_diff_key( $filters, array( 'sort' => 1 ) ) as $k => $v ) : ?><input type="hidden" name="<?php echo esc_attr( $k ); ?>" value="<?php echo esc_attr( $v ); ?>"><?php endforeach; ?>
				<?php if ( 'ar' === $lang ) : ?><input type="hidden" name="lang" value="ar"><?php endif; ?>
				<label for="smd-sort"><?php esc_html_e( 'Sort by', 'sm-marketplace' ); ?></label>
				<select id="smd-sort" name="sort" data-sm-autosubmit>
					<option value="featured" <?php selected( $sort, 'featured' ); ?>><?php esc_html_e( 'Featured first', 'sm-marketplace' ); ?></option>
					<option value="newest" <?php selected( $sort, 'newest' ); ?>><?php esc_html_e( 'Newest', 'sm-marketplace' ); ?></option>
					<option value="name" <?php selected( $sort, 'name' ); ?>><?php esc_html_e( 'Name A → Z', 'sm-marketplace' ); ?></option>
				</select>
				<noscript><button class="sm-btn sm-btn--ghost" type="submit"><?php esc_html_e( 'Apply', 'sm-marketplace' ); ?></button></noscript>
			</form>
		</div>

		<?php if ( $result['items'] ) : ?>
			<div class="smd-grid">
				<?php foreach ( $result['items'] as $c ) : ?>
					<?php
					$c_url   = sm_page_url( 'company', array( 'c' => $c['slug'] ) );
					$c_acts  = array_map( static fn( $a ) => $types['labels'][ $a ] ?? $a, (array) $c['activities'] );
					$c_level = (int) $c['verification'];
					?>
					<article class="smd-card">
						<span class="smd-card__logo<?php echo $is_pro ? ' smd-card__logo--round' : ''; ?>"><?php if ( $c['logo'] ) : ?><img src="<?php echo esc_url( $c['logo'] ); ?>" alt="" loading="lazy" decoding="async"><?php else : ?><?php echo esc_html( $c['initials'] ); ?><?php endif; ?></span>
						<div class="smd-card__body">
							<ul class="smd-card__badges" role="list">
								<?php if ( ! empty( $c['featured'] ) ) : ?><li class="smd-flag"><?php echo esc_html( $c['featured'] ); ?></li><?php endif; ?>
								<?php if ( $c_level >= 1 ) : ?><li class="smc-badge smc-badge--account"><?php sm_icon( 'check' ); ?><?php esc_html_e( 'Account verified', 'sm-marketplace' ); ?></li><?php endif; ?>
								<?php if ( $c_level >= 2 ) : ?><li class="smc-badge smc-badge--quality"><?php sm_icon( 'verified' ); ?><?php echo esc_html( sm_verified_label( $class ) ); ?></li><?php endif; ?>
							</ul>
							<h2 class="smd-card__name"><a href="<?php echo esc_url( $c_url ); ?>"><?php sm_e( $c['name'] ); ?></a></h2>
							<p class="smd-card__sub"><?php echo esc_html( ( $is_pro && ! empty( $c['title'] ) ) ? sm_t( $c['title'] ) : implode( ' · ', $c_acts ) ); ?></p>
							<p class="smd-card__place"><?php sm_icon( 'map-pin' ); ?><?php echo esc_html( sm_t( $wilayas[ $c['wilaya'] ] ?? '' ) ); ?></p>
							<div class="smd-card__actions">
								<a class="sm-btn sm-btn--primary" href="<?php echo esc_url( $c_url ); ?>"><?php esc_html_e( 'See the profile', 'sm-marketplace' ); ?></a>
								<a class="sm-btn sm-btn--ghost" href="<?php echo esc_url( $c_url . '#smc-services' ); ?>"><?php esc_html_e( 'Request a quote', 'sm-marketplace' ); ?></a>
							</div>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
			<?php if ( $result['pages'] > 1 ) : ?>
				<nav class="sm-pagination" aria-label="<?php esc_attr_e( 'Pagination', 'sm-marketplace' ); ?>">
					<?php for ( $i = 1; $i <= $result['pages']; $i++ ) : ?>
						<a href="<?php echo esc_url( add_query_arg( 'pg', $i ) ); ?>" <?php echo $i === $page_num ? 'aria-current="page"' : ''; ?>><?php echo esc_html( $i ); ?></a>
					<?php endfor; ?>
				</nav>
			<?php endif; ?>
		<?php else : ?>
			<?php sm_component( 'empty-state', array( 'icon' => $is_pro ? 'wrench' : 'company', 'title' => __( 'Nobody here yet', 'sm-marketplace' ), 'text' => __( 'Change or remove a filter.', 'sm-marketplace' ), 'action_label' => __( 'See the whole directory', 'sm-marketplace' ), 'action_url' => $base ) ); ?>
		<?php endif; ?>
	</div>
</div>
