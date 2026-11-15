<?php
/**
 * Template Name: SM — Ask for a study
 *
 * Study or inspection request sent to the platform (sm-engineering). The
 * client may name a preferred partner engineer (?engineer=ID from the list);
 * the platform decides and sends a quote.
 *
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;
get_header();
$engineer = absint( sm_theme_query_arg( 'engineer' ) );
// 0.33.0: opened from a building listing ("Ask for an inspection"), the building is already filled in.
$listing  = absint( sm_theme_query_arg( 'listing' ) );
$pre      = $listing ? sm_theme_data( 'mission_prefill', array( 'listing' => $listing ) ) : null;
$pre      = is_array( $pre ) ? $pre : array();
$d        = sm_theme_data( 'mission_form', array( 'engineer' => $engineer, 'from' => absint( sm_theme_query_arg( 'from' ) ), 'type' => sm_theme_query_arg( 'type' ) ? sm_theme_query_arg( 'type' ) : ( $pre['type'] ?? '' ) ) );
?>
<section class="sm-pagehead sm-pagehead--engineers">
	<div class="sm-container">
		<?php sm_breadcrumb( array( array( __( 'Engineering', 'sm-marketplace' ), sm_page_url( 'engineers' ) ), array( __( 'Ask for a study', 'sm-marketplace' ), '' ) ) ); ?>
		<h1 class="sm-pagehead__title"><?php esc_html_e( 'Ask the platform for a study', 'sm-marketplace' ); ?></h1>
		<p class="sm-pagehead__intro"><?php esc_html_e( 'Describe your project. The platform answers with a quote, usually within 2 working days, then a verified partner engineer carries out the study.', 'sm-marketplace' ); ?></p>
	</div>
</section>
<div class="sm-container sm-section sme-request">
	<?php if ( ! is_array( $d ) ) : ?>
		<?php sm_component( 'empty-state', array( 'icon' => 'hard-hat', 'title' => __( 'Study requests open soon', 'sm-marketplace' ), 'text' => __( 'This page needs the SM Engineering plugin.', 'sm-marketplace' ) ) ); ?>
	<?php elseif ( $d['block'] ) : ?>
		<?php
		$guest = ! is_user_logged_in();
		sm_component(
			'empty-state',
			array(
				'icon'         => 'hard-hat',
				'title'        => $d['block'],
				'text'         => $guest ? __( 'Clients, companies and professionals can ask for studies. Creating an account takes one minute.', 'sm-marketplace' ) : '',
				'action_label' => $guest ? __( 'Sign in', 'sm-marketplace' ) : '',
				'action_url'   => $guest ? add_query_arg( 'redirect_to', rawurlencode( (string) sm_page_url( 'mission-request', $engineer ? array( 'engineer' => $engineer ) : array() ) ), sm_page_url( 'login' ) ) : '',
			)
		);
		if ( $guest ) {
			echo '<p class="sme-request__alt"><a class="sm-btn sm-btn--accent" href="' . esc_url( sm_page_url( 'register', array( 'type' => 'client' ) ) ) . '">' . esc_html__( 'Create an account', 'sm-marketplace' ) . '</a></p>';
		}
		?>
	<?php else : ?>
		<div class="sme-request__grid">
			<?php sm_component( 'mission-request-form', array( 'd' => $d, 'pre' => $pre, 'engineer' => $engineer, 'listing' => $pre ? $listing : 0 ) ); ?>
			<aside class="sme-request__aside">
				<?php if ( $d['preferred'] ) : ?>
					<h2 class="sm-panel__title"><?php esc_html_e( 'Engineer you chose', 'sm-marketplace' ); ?></h2>
					<?php sm_component( 'card-engineer', array( 'engineer' => $d['preferred'], 'preview' => true ) ); ?>
					<p class="sm-help"><?php esc_html_e( 'The platform offers the mission to this engineer first, if available.', 'sm-marketplace' ); ?></p>
				<?php endif; ?>
				<div class="sm-panel">
					<h2 class="sm-panel__title"><?php esc_html_e( 'How it works', 'sm-marketplace' ); ?></h2>
					<ul class="sm-checklist">
						<li><?php sm_icon( 'check' ); ?><?php esc_html_e( 'The platform studies your request and sends you a quote.', 'sm-marketplace' ); ?></li>
						<li><?php sm_icon( 'check' ); ?><?php esc_html_e( 'You accept it from your account.', 'sm-marketplace' ); ?></li>
						<li><?php sm_icon( 'check' ); ?><?php esc_html_e( 'A verified partner engineer carries out the study.', 'sm-marketplace' ); ?></li>
						<li><?php sm_icon( 'check' ); ?><?php esc_html_e( 'The platform checks it and delivers the documents in your account.', 'sm-marketplace' ); ?></li>
					</ul>
					<p class="sm-help"><?php esc_html_e( 'Your documents are private: only the platform and the engineer of your study see them.', 'sm-marketplace' ); ?></p>
				</div>
			</aside>
		</div>
	<?php endif; ?>
</div>
<?php
get_footer();
