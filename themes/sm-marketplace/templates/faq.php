<?php
/**
 * Template Name: SM — FAQ
 *
 * Frequently asked questions (0.62.0, sm-guides 0.2.0): the themes as links to their
 * part of the page, the questions (opened with a click, without script), a box to
 * contact the team and the list of the guides.
 * Data: sm_theme_data( 'faq' ) — written in the administration (SM → Frequently asked questions).
 *
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$sm_faq = sm_theme_data( 'faq' );
get_header();
if ( ! is_array( $sm_faq ) || empty( $sm_faq['groups'] ) ) {
	echo '<div class="sm-container sm-section">';
	sm_component(
		'empty-state',
		array(
			'icon'         => 'info',
			'title'        => __( 'No question for the moment', 'sm-marketplace' ),
			'text'         => __( 'Write to the ReuSteel team: we answer by e-mail.', 'sm-marketplace' ),
			'action_label' => __( 'Contact the team', 'sm-marketplace' ),
			'action_url'   => sm_page_url( 'contact' ),
		)
	);
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
$sm_guides = sm_theme_data( 'guides' );
$sm_guides = is_array( $sm_guides ) && ! empty( $sm_guides['items'] ) ? $sm_guides['items'] : array();
?>
<section class="sm-pagehead">
	<div class="sm-container">
		<?php sm_breadcrumb( array( array( __( 'Frequently asked questions', 'sm-marketplace' ), '' ) ) ); ?>
		<h1 class="sm-pagehead__title"><?php esc_html_e( 'Frequently asked questions', 'sm-marketplace' ); ?></h1>
		<p class="sm-pagehead__intro"><?php esc_html_e( 'Short answers to the questions we are asked most: accounts, listings, quotes, engineering and tools. To go further, each answer leads to the right guide or page.', 'sm-marketplace' ); ?></p>
		<?php if ( $sm_others ) : ?>
			<p class="sm-guide__meta"><span><?php esc_html_e( 'Also in', 'sm-marketplace' ); ?> <?php echo implode( ' · ', $sm_others ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped above. ?></span></p>
		<?php endif; ?>
		<nav class="sm-faq__themes" aria-label="<?php esc_attr_e( 'Themes', 'sm-marketplace' ); ?>">
			<?php foreach ( $sm_faq['groups'] as $sm_g ) : ?>
				<a href="#<?php echo esc_attr( $sm_g['id'] ); ?>"><?php echo esc_html( $sm_g['title'] ); ?></a>
			<?php endforeach; ?>
		</nav>
	</div>
</section>
<div class="sm-container sm-section">
	<div class="sm-faq">
		<div class="sm-faq__main">
			<?php foreach ( $sm_faq['groups'] as $sm_g ) : ?>
				<section class="sm-faq__group" id="<?php echo esc_attr( $sm_g['id'] ); ?>" aria-labelledby="<?php echo esc_attr( $sm_g['id'] ); ?>-t">
					<h2 id="<?php echo esc_attr( $sm_g['id'] ); ?>-t"><?php echo esc_html( $sm_g['title'] ); ?></h2>
					<?php foreach ( $sm_g['items'] as $sm_q ) : ?>
						<?php sm_faq_question( $sm_q ); ?>
					<?php endforeach; ?>
				</section>
			<?php endforeach; ?>
			<p class="sm-faq__note"><?php esc_html_e( 'Answers on how ReuSteel works. No market price and no statistics.', 'sm-marketplace' ); ?></p>
		</div>
		<aside class="sm-faq__aside">
			<div class="sm-faq__box">
				<h2><?php esc_html_e( 'Can’t find your answer?', 'sm-marketplace' ); ?></h2>
				<p><?php esc_html_e( 'Write to us: the ReuSteel team answers by e-mail.', 'sm-marketplace' ); ?></p>
				<a class="sm-btn sm-btn--accent sm-btn--block" href="<?php echo esc_url( sm_page_url( 'contact' ) ); ?>"><?php esc_html_e( 'Contact the team', 'sm-marketplace' ); ?></a>
			</div>
			<?php if ( $sm_guides ) : ?>
				<div class="sm-faq__box">
					<h2><?php esc_html_e( 'Practical guides', 'sm-marketplace' ); ?></h2>
					<ul class="sm-guide__tools">
						<?php foreach ( $sm_guides as $sm_gd ) : ?>
							<li><a href="<?php echo esc_url( $sm_gd['url'] ); ?>"><?php sm_icon( 'arrow-end' ); ?><?php echo esc_html( $sm_gd['title'] ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>
		</aside>
	</div>
</div>
<?php
get_footer();
