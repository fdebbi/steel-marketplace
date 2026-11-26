<?php
/**
 * A guide (0.61.0): summary, date, other languages, text with its table of contents,
 * the tools of the site it links to, then the other guides.
 *
 * @var array $args { data: guide of sm-guides }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$d      = $args['data'];
$names  = sm_guide_tool_labels();
$cta    = sm_guide_cta_labels();
$lang   = sm_theme_current_language();
$langs  = array( 'fr' => 'Français', 'ar' => 'العربية', 'en' => 'English' );
$others = array();
foreach ( (array) $d['languages'] as $code ) {
	if ( $code !== $lang && isset( $langs[ $code ] ) && function_exists( 'sm_language_url' ) ) {
		$others[] = '<a href="' . esc_url( sm_language_url( $code ) ) . '" lang="' . esc_attr( $code ) . '" hreflang="' . esc_attr( $code ) . '">' . esc_html( $langs[ $code ] ) . '</a>';
	}
}
$main = $d['tools'][0] ?? null;
$toc  = '';
foreach ( (array) $d['toc'] as $t ) {
	$toc .= '<li><a href="#' . esc_attr( $t['id'] ) . '">' . esc_html( $t['title'] ) . '</a></li>';
}
$text_attrs = $d['fallback'] ? ' lang="fr" dir="ltr"' : '';
?>
<section class="sm-pagehead">
	<div class="sm-container">
		<?php sm_breadcrumb( array( array( __( 'Guides', 'sm-marketplace' ), sm_page_url( 'guides' ) ), array( $d['title'], '' ) ) ); ?>
		<h1 class="sm-pagehead__title"<?php echo $text_attrs; // phpcs:ignore WordPress.Security.EscapeOutput -- fixed attributes. ?>><?php echo esc_html( $d['title'] ); ?></h1>
		<?php if ( '' !== $d['summary'] ) : ?>
			<p class="sm-pagehead__intro"<?php echo $text_attrs; // phpcs:ignore WordPress.Security.EscapeOutput ?>><?php echo esc_html( $d['summary'] ); ?></p>
		<?php endif; ?>
		<p class="sm-guide__meta">
			<span><?php echo esc_html( sprintf( /* translators: %s: date. */ __( 'Updated on %s', 'sm-marketplace' ), sm_date( $d['updated'] ) ) ); ?></span>
			<?php if ( $others ) : ?>
				<span><?php esc_html_e( 'Also in', 'sm-marketplace' ); ?> <?php echo implode( ' · ', $others ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped above. ?></span>
			<?php endif; ?>
		</p>
	</div>
</section>
<div class="sm-container sm-section">
	<?php if ( $d['fallback'] ) : ?>
		<p class="sm-guide__fallback"><?php esc_html_e( 'This guide is not written in your language yet: here is the French text.', 'sm-marketplace' ); ?></p>
	<?php endif; ?>
	<div class="sm-guide">
		<article class="sm-guide__text sm-prose"<?php echo $text_attrs; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
			<?php echo wp_kses_post( $d['html'] ); ?>
		</article>
		<aside class="sm-guide__aside">
			<?php if ( $toc ) : ?>
				<details class="sm-guide__box sm-guide__toc-m">
					<summary><?php esc_html_e( 'In this guide', 'sm-marketplace' ); ?></summary>
					<ol<?php echo $text_attrs; // phpcs:ignore WordPress.Security.EscapeOutput ?>><?php echo $toc; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped above. ?></ol>
				</details>
				<nav class="sm-guide__box sm-guide__toc" aria-label="<?php esc_attr_e( 'In this guide', 'sm-marketplace' ); ?>">
					<p class="sm-guide__boxtitle"><?php esc_html_e( 'In this guide', 'sm-marketplace' ); ?></p>
					<ol<?php echo $text_attrs; // phpcs:ignore WordPress.Security.EscapeOutput ?>><?php echo $toc; // phpcs:ignore WordPress.Security.EscapeOutput ?></ol>
				</nav>
			<?php endif; ?>
			<?php if ( $d['tools'] ) : ?>
				<div class="sm-guide__box">
					<p class="sm-guide__boxtitle"><?php esc_html_e( 'Linked tools', 'sm-marketplace' ); ?></p>
					<ul class="sm-guide__tools">
						<?php foreach ( $d['tools'] as $t ) : ?>
							<li><a href="<?php echo esc_url( $t['url'] ); ?>"><?php sm_icon( 'arrow-end' ); ?><?php echo esc_html( $names[ $t['key'] ] ?? $t['key'] ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</div>
				<?php if ( $main ) : ?>
					<a class="sm-btn sm-btn--accent sm-btn--block" href="<?php echo esc_url( $main['url'] ); ?>"><?php echo esc_html( $cta[ $main['key'] ] ?? ( $names[ $main['key'] ] ?? '' ) ); ?></a>
				<?php endif; ?>
			<?php endif; ?>
		</aside>
	</div>
	<?php if ( $d['others'] ) : ?>
		<section class="sm-guide__more">
			<h2><?php esc_html_e( 'Other guides', 'sm-marketplace' ); ?></h2>
			<div class="sm-guidegrid">
				<?php foreach ( $d['others'] as $g ) : ?>
					<?php get_template_part( 'template-parts/guides/card', null, array( 'guide' => $g, 'heading' => 'h3' ) ); ?>
				<?php endforeach; ?>
			</div>
		</section>
	<?php endif; ?>
</div>
