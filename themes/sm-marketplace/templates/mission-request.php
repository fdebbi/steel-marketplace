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
$d        = sm_theme_data( 'mission_form', array( 'engineer' => $engineer, 'type' => sm_theme_query_arg( 'type' ) ) );
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
			<?php sm_theme_form_open( 'mission_request', array( 'class' => 'sm-form sm-panel', 'enctype' => 'multipart/form-data' ) ); ?>
				<?php sm_theme_notices( true ); ?>
				<?php
				$services = (array) ( $d['services'] ?? array() );
				$chosen   = (string) sm_theme_old( 'type', $d['type'] );
				$ticked   = (array) sm_theme_old( 'prestations', array() );
				?>
				<div class="sm-field">
					<label for="m-type"><?php esc_html_e( 'Engineering service', 'sm-marketplace' ); ?></label>
					<select id="m-type" name="type" required data-sm-service<?php sm_theme_aria( 'type' ); ?>>
						<option value=""><?php esc_html_e( 'Select', 'sm-marketplace' ); ?></option>
						<?php foreach ( $services as $sv ) : ?><option value="<?php echo esc_attr( $sv['code'] ); ?>" <?php selected( $chosen, $sv['code'] ); ?>><?php echo esc_html( $sv['title'] ); ?></option><?php endforeach; ?>
					</select>
					<?php sm_theme_error( 'type' ); ?>
				</div>
				<?php foreach ( $services as $sv ) : ?>
					<?php
					if ( ! $sv['items'] ) {
						continue;
					}
					$on   = $chosen === $sv['code'];
					$mine = array_map( 'intval', (array) ( $ticked[ $sv['code'] ] ?? array() ) );
					?>
					<fieldset class="sme-prest" data-sm-prest="<?php echo esc_attr( $sv['code'] ); ?>"<?php echo $on ? '' : ' hidden disabled'; ?>>
						<legend><?php esc_html_e( 'Prestations wanted', 'sm-marketplace' ); ?></legend>
						<div class="sme-prest__group">
							<?php foreach ( $sv['items'] as $i => $item ) : ?>
								<label><input type="checkbox" name="prestations[<?php echo esc_attr( $sv['code'] ); ?>][]" value="<?php echo (int) $i; ?>" <?php checked( in_array( (int) $i, $mine, true ) ); ?>><span><?php echo esc_html( $item ); ?></span></label>
							<?php endforeach; ?>
						</div>
						<?php if ( $on ) : ?><?php sm_theme_error( 'prestations' ); ?><?php endif; ?>
					</fieldset>
				<?php endforeach; ?>
				<div class="sm-field">
					<label for="m-title"><?php esc_html_e( 'Project', 'sm-marketplace' ); ?></label>
					<input id="m-title" name="title" dir="auto" maxlength="200" required value="<?php echo esc_attr( sm_theme_old( 'title' ) ); ?>" placeholder="<?php esc_attr_e( 'e.g. Storage hangar 20 × 40 m', 'sm-marketplace' ); ?>"<?php sm_theme_aria( 'title' ); ?>>
					<?php sm_theme_error( 'title' ); ?>
				</div>
				<div class="sm-field">
					<label for="m-desc"><?php esc_html_e( 'Description', 'sm-marketplace' ); ?></label>
					<textarea id="m-desc" name="description" rows="7" dir="auto" maxlength="5000" required placeholder="<?php esc_attr_e( 'Dimensions, use, loads (crane, snow, wind), soil, what you expect from the study, existing documents…', 'sm-marketplace' ); ?>"<?php sm_theme_aria( 'description' ); ?>><?php echo esc_textarea( sm_theme_old( 'description' ) ); ?></textarea>
					<?php sm_theme_error( 'description' ); ?>
				</div>
				<div class="sm-field sm-field--row">
					<div>
						<label for="m-wilaya"><?php esc_html_e( 'Wilaya of the project', 'sm-marketplace' ); ?></label>
						<select id="m-wilaya" name="wilaya" required<?php sm_theme_aria( 'wilaya' ); ?>>
							<option value=""><?php esc_html_e( 'Select', 'sm-marketplace' ); ?></option>
							<?php foreach ( sm_theme_wilayas() as $code => $name ) : ?><option value="<?php echo esc_attr( $code ); ?>" <?php selected( sm_theme_old( 'wilaya' ), (string) $code ); ?>><?php echo esc_html( $code . ' — ' . sm_t( $name ) ); ?></option><?php endforeach; ?>
						</select>
						<?php sm_theme_error( 'wilaya' ); ?>
					</div>
					<div>
						<label for="m-date"><?php esc_html_e( 'Wanted for', 'sm-marketplace' ); ?> <small class="sm-optional"><?php esc_html_e( 'optional', 'sm-marketplace' ); ?></small></label>
						<input id="m-date" name="needed_by" type="date" dir="ltr" min="<?php echo esc_attr( gmdate( 'Y-m-d' ) ); ?>" value="<?php echo esc_attr( sm_theme_old( 'needed_by' ) ); ?>"<?php sm_theme_aria( 'needed_by' ); ?>>
						<?php sm_theme_error( 'needed_by' ); ?>
					</div>
				</div>
				<div class="sm-field<?php echo $d['engineers'] ? ' sm-field--row' : ''; ?>">
					<div>
						<label for="m-budget"><?php esc_html_e( 'Indicative budget (DA)', 'sm-marketplace' ); ?> <small class="sm-optional"><?php esc_html_e( 'optional', 'sm-marketplace' ); ?></small></label>
						<input id="m-budget" name="budget" inputmode="numeric" dir="ltr" value="<?php echo esc_attr( sm_theme_old( 'budget' ) ); ?>">
					</div>
					<?php if ( $d['engineers'] ) : ?>
					<div>
						<label for="m-eng"><?php esc_html_e( 'Preferred engineer', 'sm-marketplace' ); ?> <small class="sm-optional"><?php esc_html_e( 'optional', 'sm-marketplace' ); ?></small></label>
						<select id="m-eng" name="engineer">
							<option value=""><?php esc_html_e( 'Let the platform choose', 'sm-marketplace' ); ?></option>
							<?php foreach ( $d['engineers'] as $eid => $label ) : ?><option value="<?php echo (int) $eid; ?>" <?php selected( (int) sm_theme_old( 'engineer', $engineer ), (int) $eid ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?>
						</select>
					</div>
					<?php endif; ?>
				</div>
				<label class="sm-upload sm-upload--small">
					<input type="file" name="files[]" multiple data-sm-filename>
					<?php sm_icon( 'upload', 'sm-upload__icon' ); ?>
					<strong><?php esc_html_e( 'Plans, photos, documents', 'sm-marketplace' ); ?> <small class="sm-optional"><?php esc_html_e( 'optional', 'sm-marketplace' ); ?></small></strong>
					<small data-sm-filename-label><?php echo esc_html( sprintf( /* translators: 1: number of files, 2: MB, 3: extensions. */ __( 'Up to %1$d files of %2$d MB (%3$s).', 'sm-marketplace' ), (int) $d['max_files'], (int) $d['file_mb'], strtoupper( implode( ', ', $d['extensions'] ) ) ) ); ?></small>
				</label>
				<button class="sm-btn sm-btn--accent sm-btn--lg" type="submit"><?php sm_icon( 'hard-hat' ); ?><?php esc_html_e( 'Send my request to the platform', 'sm-marketplace' ); ?></button>
			</form>
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
