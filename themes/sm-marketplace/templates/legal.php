<?php
/**
 * Template Name: SM — Legal page
 *
 * Terms of use and privacy policy (0.63.0, sm-core 0.10.0): version and date,
 * switch between the two texts, articles with a table of contents (folded on a
 * phone) and a box to contact the team.
 * Data: sm_theme_data( 'legal', { key } ) — texts and company information in
 * the administration (SM → Legal information, and the editor of each page).
 *
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$sm_key = '';
$sm_map = (array) get_option( 'sm_theme_pages', array() );
foreach ( array( 'terms', 'privacy' ) as $sm_k ) {
	if ( (int) ( $sm_map[ $sm_k ] ?? 0 ) === (int) get_queried_object_id() ) {
		$sm_key = $sm_k;
	}
}
$sm_legal = $sm_key ? sm_theme_data( 'legal', array( 'key' => $sm_key ) ) : null;
get_header();
if ( ! is_array( $sm_legal ) ) {
	echo '<div class="sm-container sm-section">';
	while ( have_posts() ) {
		the_post();
		echo '<h1>' . esc_html( get_the_title() ) . '</h1>';
		the_content();
	}
	echo '</div>';
	get_footer();
	return;
}
$sm_lang   = sm_theme_current_language();
$sm_langs  = array( 'fr' => 'Français', 'ar' => 'العربية', 'en' => 'English' );
$sm_others = array();
foreach ( $sm_langs as $sm_code => $sm_name ) {
	if ( $sm_code !== $sm_lang && isset( sm_theme_languages()[ $sm_code ] ) ) {
		$sm_others[] = '<a href="' . esc_url( sm_theme_language_url( $sm_code ) ) . '" lang="' . esc_attr( $sm_code ) . '" hreflang="' . esc_attr( $sm_code ) . '">' . esc_html( $sm_name ) . '</a>';
	}
}
$sm_first = 'terms' === $sm_legal['key'];
$sm_tabs  = array(
	array( $sm_legal['short'], '', true ),
	array( $sm_legal['other']['title'], $sm_legal['other']['url'], false ),
);
if ( ! $sm_first ) {
	$sm_tabs = array_reverse( $sm_tabs );
}
$sm_toc = '';
foreach ( $sm_legal['toc'] as $sm_t ) {
	$sm_toc .= '<li><a href="#' . esc_attr( $sm_t['id'] ) . '">' . esc_html( $sm_t['title'] ) . '</a></li>';
}
?>
<section class="sm-pagehead">
	<div class="sm-container">
		<?php sm_breadcrumb( array( array( $sm_legal['title'], '' ) ) ); ?>
		<h1 class="sm-pagehead__title"><?php echo esc_html( $sm_legal['title'] ); ?></h1>
		<p class="sm-legal__meta">
			<span>
				<?php
				/* translators: %s: version number of the texts. */
				echo esc_html( sprintf( __( 'Version %s', 'sm-marketplace' ), $sm_legal['version'] ) );
				if ( '' !== $sm_legal['date'] ) {
					/* translators: %s: date. */
					echo ' · ' . esc_html( sprintf( __( 'in force from %s', 'sm-marketplace' ), sm_date( $sm_legal['date'] ) ) );
				}
				?>
			</span>
			<?php if ( $sm_others ) : ?>
				<span><?php esc_html_e( 'Also in', 'sm-marketplace' ); ?> <?php echo implode( ' · ', $sm_others ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped above. ?></span>
			<?php endif; ?>
		</p>
		<?php if ( ! empty( $sm_legal['fallback'] ) ) : ?>
			<p class="sm-legal__meta"><?php esc_html_e( 'This text is not written in your language yet: here is the French version.', 'sm-marketplace' ); ?></p>
		<?php endif; ?>
		<nav class="sm-legal__switch" aria-label="<?php esc_attr_e( 'Legal pages', 'sm-marketplace' ); ?>">
			<?php foreach ( $sm_tabs as $sm_tab ) : ?>
				<?php if ( $sm_tab[2] ) : ?>
					<span aria-current="page"><?php echo esc_html( $sm_tab[0] ); ?></span>
				<?php elseif ( '' !== $sm_tab[1] ) : ?>
					<a href="<?php echo esc_url( $sm_tab[1] ); ?>"><?php echo esc_html( $sm_tab[0] ); ?></a>
				<?php endif; ?>
			<?php endforeach; ?>
		</nav>
	</div>
</section>
<div class="sm-container sm-section">
	<div class="sm-legal">
		<div class="sm-legal__main"<?php echo ! empty( $sm_legal['fallback'] ) ? ' lang="fr" dir="ltr"' : ''; ?>>
			<details class="sm-legal__box sm-legal__toc-m">
				<summary><?php esc_html_e( 'Contents', 'sm-marketplace' ); ?></summary>
				<ol><?php echo $sm_toc; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped above. ?></ol>
			</details>
			<?php echo sm_legal_kses( $sm_legal['html'] ); // phpcs:ignore WordPress.Security.EscapeOutput -- filtered. ?>
		</div>
		<aside class="sm-legal__aside">
			<nav class="sm-legal__box sm-legal__toc-d" aria-label="<?php esc_attr_e( 'Contents', 'sm-marketplace' ); ?>">
				<h2><?php esc_html_e( 'Contents', 'sm-marketplace' ); ?></h2>
				<ol><?php echo $sm_toc; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped above. ?></ol>
			</nav>
			<div class="sm-legal__box">
				<h2><?php echo esc_html( $sm_first ? __( 'A question?', 'sm-marketplace' ) : __( 'A question about your data?', 'sm-marketplace' ) ); ?></h2>
				<p><?php esc_html_e( 'Write to us: the ReuSteel team answers by e-mail.', 'sm-marketplace' ); ?></p>
				<a class="sm-btn sm-btn--accent sm-btn--block" href="<?php echo esc_url( sm_page_url( 'contact' ) ); ?>"><?php esc_html_e( 'Contact the team', 'sm-marketplace' ); ?></a>
			</div>
		</aside>
	</div>
</div>
<?php
get_footer();
