<?php
/**
 * Card of a guide (list of the guides, « Other guides »).
 *
 * @var array $args { guide: { url, topic, title, excerpt, sections, tools }, heading }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$g     = $args['guide'];
$tag   = $args['heading'] ?? 'h2';
$names = sm_guide_tool_short();
$tools = array_filter( array_map( static fn( $t ) => $names[ $t['key'] ] ?? '', (array) $g['tools'] ) );
?>
<a class="sm-guidecard" href="<?php echo esc_url( $g['url'] ); ?>">
	<?php if ( '' !== $g['topic'] ) : ?>
		<span class="sm-guidecard__topic"><?php echo esc_html( $g['topic'] ); ?></span>
	<?php endif; ?>
	<<?php echo tag_escape( $tag ); ?> class="sm-guidecard__title"><?php echo esc_html( $g['title'] ); ?></<?php echo tag_escape( $tag ); ?>>
	<?php if ( '' !== $g['excerpt'] ) : ?>
		<p class="sm-guidecard__text"><?php echo esc_html( $g['excerpt'] ); ?></p>
	<?php endif; ?>
	<span class="sm-guidecard__foot">
		<span><b><?php esc_html_e( 'Read the guide', 'sm-marketplace' ); ?></b><?php if ( $g['sections'] ) : ?> · <?php echo esc_html( sprintf( /* translators: %d: number of sections of a guide. */ _n( '%d section', '%d sections', (int) $g['sections'], 'sm-marketplace' ), (int) $g['sections'] ) ); ?><?php endif; ?></span>
		<?php if ( $tools ) : ?>
			<span><?php echo esc_html( sprintf( /* translators: %s: list of tools of the site. */ __( 'Linked tools: %s', 'sm-marketplace' ), implode( ', ', $tools ) ) ); ?></span>
		<?php endif; ?>
	</span>
</a>
