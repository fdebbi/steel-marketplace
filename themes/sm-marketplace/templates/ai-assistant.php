<?php
/**
 * Template Name: SM — AI assistant
 *
 * Public page of the AI tools (0.45.0): what each tool does, what it costs and
 * where it is. A visitor is asked to sign in; a member without credits is
 * asked to upgrade his plan or buy credits; the others open the tool. The
 * smart search is free for everybody. Data: ai_catalog (sm-ai).
 *
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

get_header();

$sm_cat = sm_theme_data( 'ai_catalog' );
$sm_svc = is_array( $sm_cat ) ? (array) $sm_cat['services'] : array();
$sm_acc = static fn( $tool ) => sm_page_url( 'account', array( 'view' => 'ai', 'tool' => $tool ) );

// id => icon, text, example, where, button, url.
$sm_tools = array(
	'search'    => array( 'search', __( 'Write what you need in one sentence, in French, Arabic or darija: the search turns it into filters and shows the matching listings.', 'sm-marketplace' ), __( '“20 used IPE 200 delivered to Oran”', 'sm-marketplace' ), '', __( 'Search', 'sm-marketplace' ), sm_page_url( 'search' ) . '#smai-q' ),
	'describe'  => array( 'edit', __( 'In the publication form, the AI sums up what you entered and writes a clear, detailed description. You change it as you like before publishing.', 'sm-marketplace' ), '', __( 'Publication form', 'sm-marketplace' ), __( 'Publish a listing', 'sm-marketplace' ), sm_page_url( 'account', array( 'view' => 'publish' ) ) ),
	'cutlist'   => array( 'file', __( 'Bill of quantities, quotation, purchase order, delivery note, invoice or inventory (Excel, PDF or photo): the AI reads the profiles and the plates, with their weight. You check, then you choose: cutting optimiser, request for quotation or your warehouse.', 'sm-marketplace' ), '', '', __( 'Read a document', 'sm-marketplace' ), $sm_acc( 'document' ) ),
	'recognize' => array( 'camera', __( 'One or two photos (end view if possible, with a tape measure): up to 4 possible profiles, checked against the catalogue, and the questions that remove the doubt.', 'sm-marketplace' ), '', '', __( 'Recognise a profile', 'sm-marketplace' ), $sm_acc( 'recognize' ) ),
	'listing'   => array( 'package', __( 'Photos of your steel and a few words: the assistant proposes the category, the profile, the titles in French and Arabic and the description of a new listing.', 'sm-marketplace' ), '', '', __( 'Prepare a listing', 'sm-marketplace' ), $sm_acc( 'listing' ) ),
);
?>
<section class="sm-pagehead">
	<div class="sm-container">
		<?php sm_breadcrumb( array( array( __( 'AI assistant', 'sm-marketplace' ), '' ) ) ); ?>
		<h1 class="sm-pagehead__title smai-page__title"><?php esc_html_e( 'Assistant', 'sm-marketplace' ); ?> <?php sm_ai_badge(); ?></h1>
		<p class="sm-pagehead__intro"><?php esc_html_e( 'Tools that save you time: search, describe, recognise, list. They give suggestions to check: weights always come from the weight engine and the AI never replaces an engineer.', 'sm-marketplace' ); ?></p>
	</div>
</section>

<div class="sm-container sm-section smai-page">
	<?php if ( ! $sm_svc ) : ?>
		<?php sm_component( 'empty-state', array( 'icon' => 'ai', 'title' => __( 'AI assistant', 'sm-marketplace' ), 'text' => __( 'This module is not active yet.', 'sm-marketplace' ) ) ); ?>
	<?php else : ?>
		<div class="smai-cards">
			<?php
			foreach ( $sm_tools as $id => list( $icon, $text, $example, $where, $button, $url ) ) :
				if ( empty( $sm_svc[ $id ] ) ) {
					continue;
				}
				$s = $sm_svc[ $id ];
				?>
				<article class="smai-card" id="ai-<?php echo esc_attr( $id ); ?>" data-state="<?php echo esc_attr( $s['state'] ); ?>">
					<span class="smai-card__icon"><?php sm_icon( $icon ); ?></span>
					<h2 class="smai-card__title"><?php echo esc_html( $s['label'] ); ?></h2>
					<p><?php echo esc_html( $text ); ?></p>
					<?php if ( $example ) : ?><p class="smai-card__ex" dir="auto"><?php echo esc_html( $example ); ?></p><?php endif; ?>
					<p class="smai-card__meta">
						<?php // 0.55.0: no price here — it is shown on the button where the tool is used. ?>
						<?php if ( 0 === (int) $s['credits'] ) : ?>
							<span class="is-free"><?php esc_html_e( 'Free · for everybody', 'sm-marketplace' ); ?></span>
						<?php endif; ?>
						<?php if ( $where ) : ?><span><?php echo esc_html( $where ); ?></span><?php endif; ?>
					</p>
					<div class="smai-card__foot">
						<?php if ( 'ok' === $s['state'] ) : ?>
							<a class="sm-btn sm-btn--ai sm-btn--block" href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $button ); ?><?php sm_icon( 'arrow-end' ); ?></a>
							<?php if ( null !== $s['remaining'] && $s['credits'] > 0 ) : ?>
								<p class="smai-card__left"><?php echo esc_html( sprintf( /* translators: %d: AI credits left. */ _n( '%d credit left', '%d credits left', (int) $s['remaining'], 'sm-marketplace' ), (int) $s['remaining'] ) ); ?></p>
							<?php endif; ?>
						<?php else : ?>
							<span class="sm-btn sm-btn--block smai-card__off" aria-disabled="true"><?php echo esc_html( 'describe' === $id ? __( 'Write with the AI', 'sm-marketplace' ) : $button ); ?></span>
							<?php sm_component( 'ai-gate', array( 'state' => $s['state'], 'reason' => $s['reason'] ) ); ?>
						<?php endif; ?>
					</div>
				</article>
			<?php endforeach; ?>
			<?php
			// Business report (0.53.0, sm-insights): open it with the Business plan, else see the plan.
			$biz = sm_theme_data( 'insights_enabled' ) ? sm_theme_data( 'insights_report', array() ) : null;
			$biz_ok = is_array( $biz ) && 'ok' === ( $biz['state'] ?? '' );
			?>
			<article class="smai-card smai-card--soon" id="ai-business">
				<span class="smai-card__icon"><?php sm_icon( 'chart' ); ?></span>
				<h2 class="smai-card__title"><?php esc_html_e( 'Business report', 'sm-marketplace' ); ?></h2>
				<p><?php esc_html_e( 'Follow your market like a pro: every day, the average asked prices of new and reused steel, the steel on sale, the most searched and best-selling profiles and what is asked and little offered, by region, with a summary written by the AI.', 'sm-marketplace' ); ?></p>
				<p class="smai-card__meta"><span class="is-biz"><?php esc_html_e( 'Included in the Business plan', 'sm-marketplace' ); ?></span></p>
				<div class="smai-card__foot">
					<?php if ( $biz_ok ) : ?>
						<a class="sm-btn sm-btn--ai sm-btn--block" href="<?php echo esc_url( sm_page_url( 'account', array( 'view' => 'report' ) ) ); ?>"><?php esc_html_e( 'Open the report', 'sm-marketplace' ); ?><?php sm_icon( 'arrow-end' ); ?></a>
					<?php else : ?>
						<a class="sm-btn sm-btn--ai-outline sm-btn--block" href="<?php echo esc_url( is_user_logged_in() ? sm_page_url( 'account', array( 'view' => 'plan' ) ) : sm_page_url( 'pricing' ) ); ?>"><?php esc_html_e( 'See the Business plan', 'sm-marketplace' ); ?></a>
					<?php endif; ?>
				</div>
			</article>
		</div>
	<?php endif; ?>
</div>
<?php
get_footer();
