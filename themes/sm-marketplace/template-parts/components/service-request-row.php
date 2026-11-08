<?php
/**
 * One service request, seen by the provider (box "received") or the client
 * (box "sent"), with the actions open to each side.
 *
 * @var array $args { request: array }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$r       = $args['request'];
$labels  = sm_service_request_labels();
$wilayas = sm_theme_wilayas();
$recv    = 'received' === $r['box'];
$other   = (array) $r['other'];
$open    = in_array( $r['status'], array( 'new', 'quoted' ), true );
$contact = 'accepted' === $r['status'] || $recv;
$old     = sm_theme_old_all();
$mine    = $old && (int) ( $old['request_id'] ?? 0 ) === (int) $r['id']; // This row's quote form was just sent back with errors.
$qv      = static fn( $k, $d ) => $mine && isset( $old[ $k ] ) && ! is_array( $old[ $k ] ) ? (string) $old[ $k ] : (string) $d;
$err     = static function ( $k ) use ( $mine ) {
	if ( $mine ) {
		sm_theme_error( $k );
	}
};
?>
<li class="sms-req sms-req--<?php echo esc_attr( $r['status'] ); ?>" id="req-<?php echo esc_attr( $r['id'] ); ?>">
	<div class="sms-req__head">
		<div>
			<p class="smp-row__title">
				<?php if ( $r['service_id'] ) : ?><a href="<?php echo esc_url( sm_page_url( 'service', array( 'id' => $r['service_id'] ) ) ); ?>"><?php sm_e( $r['service_title'] ); ?></a><?php else : ?><?php esc_html_e( 'Service removed', 'sm-marketplace' ); ?><?php endif; ?>
			</p>
			<p class="smp-row__meta">
				<span class="sms-rstatus sms-rstatus--<?php echo esc_attr( $r['status'] ); ?>"><?php echo esc_html( $labels[ $r['status'] ] ?? $r['status'] ); ?></span>
				<?php if ( $other ) : ?><span><?php sm_icon( 'company' ); ?> <?php if ( $other['slug'] ) : ?><a href="<?php echo esc_url( sm_page_url( 'company', array( 'c' => $other['slug'] ) ) ); ?>"><?php sm_e( $other['name'] ); ?></a><?php else : ?><?php sm_e( $other['name'] ); ?><?php endif; ?></span><?php endif; ?>
				<span><?php sm_icon( 'map-pin' ); ?> <?php echo esc_html( sm_t( $wilayas[ $r['wilaya'] ] ?? '' ) ); ?></span>
				<span><?php echo esc_html( sm_ago( $r['date'] ) ); ?></span>
			</p>
		</div>
	</div>
	<dl class="sms-req__facts">
		<?php if ( $r['needed_by'] ) : ?><div><dt><?php esc_html_e( 'Needed by', 'sm-marketplace' ); ?></dt><dd><?php echo esc_html( sm_date( $r['needed_by'] ) ); ?></dd></div><?php endif; ?>
		<?php if ( $r['budget'] > 0 ) : ?><div><dt><?php esc_html_e( 'Indicative budget', 'sm-marketplace' ); ?></dt><dd><?php echo esc_html( sm_money( $r['budget'] ) ); ?></dd></div><?php endif; ?>
		<?php if ( $r['quote_amount'] > 0 ) : ?>
			<div class="sms-req__quote"><dt><?php esc_html_e( 'Quote', 'sm-marketplace' ); ?></dt><dd><strong><?php echo esc_html( sm_money( $r['quote_amount'] ) ); ?></strong> · <?php echo esc_html( sprintf( /* translators: %d: days. */ _n( '%d day', '%d days', (int) $r['quote_days'], 'sm-marketplace' ), (int) $r['quote_days'] ) ); ?></dd></div>
		<?php endif; ?>
	</dl>
	<details class="sms-req__more"<?php echo $recv && 'new' === $r['status'] ? ' open' : ''; ?>>
		<summary><?php esc_html_e( 'Project details', 'sm-marketplace' ); ?></summary>
		<p class="sms-req__msg" dir="auto"><?php echo nl2br( esc_html( $r['message'] ) ); ?></p>
		<?php if ( $r['files'] ) : ?>
			<ul class="sms-files" role="list">
				<?php foreach ( $r['files'] as $f ) : ?><li><a href="<?php echo esc_url( $f['url'] ); ?>" target="_blank" rel="noopener"><?php sm_icon( 'file' ); ?><?php echo esc_html( $f['name'] ); ?></a></li><?php endforeach; ?>
			</ul>
		<?php endif; ?>
		<?php if ( $r['quote_message'] ) : ?>
			<p class="sms-req__reply" dir="auto"><strong><?php echo esc_html( $recv ? __( 'Your answer:', 'sm-marketplace' ) : __( 'Answer of the provider:', 'sm-marketplace' ) ); ?></strong> <?php echo nl2br( esc_html( $r['quote_message'] ) ); ?></p>
		<?php endif; ?>
	</details>

	<?php if ( $other && $contact && ( $other['phone'] || $other['email'] ) ) : ?>
		<p class="sms-req__contact">
			<?php if ( $other['phone'] ) : ?><a class="sm-btn sm-btn--ghost sm-btn--sm" href="<?php echo esc_url( 'tel:' . preg_replace( '/[^\d+]/', '', $other['phone'] ) ); ?>" dir="ltr"><?php sm_icon( 'phone' ); ?><?php echo esc_html( $other['phone'] ); ?></a><?php endif; ?>
			<?php if ( $other['email'] ) : ?><a class="sm-btn sm-btn--ghost sm-btn--sm" href="<?php echo esc_url( 'mailto:' . $other['email'] ); ?>" dir="ltr"><?php sm_icon( 'mail' ); ?><?php echo esc_html( $other['email'] ); ?></a><?php endif; ?>
		</p>
	<?php endif; ?>

	<?php if ( $recv && $open ) : ?>
		<details class="sms-req__answer"<?php echo 'new' === $r['status'] || $mine ? ' open' : ''; ?>>
			<summary class="sm-btn sm-btn--accent sm-btn--sm"><?php sm_icon( 'rfq' ); ?><?php echo esc_html( 'quoted' === $r['status'] ? __( 'Update the quote', 'sm-marketplace' ) : __( 'Answer with a quote', 'sm-marketplace' ) ); ?></summary>
			<?php sm_theme_form_open( 'service_reply', array( 'class' => 'sm-form sms-quote' ) ); ?>
				<input type="hidden" name="request_id" value="<?php echo esc_attr( $r['id'] ); ?>">
				<div class="sm-field-grid">
					<div class="sm-field">
						<label for="q-amount-<?php echo esc_attr( $r['id'] ); ?>"><?php esc_html_e( 'Amount of the quote', 'sm-marketplace' ); ?> <small>(<?php echo esc_html( sm_currency_label() ); ?>)</small></label>
						<input id="q-amount-<?php echo esc_attr( $r['id'] ); ?>" name="quote_amount" inputmode="decimal" dir="ltr" value="<?php echo esc_attr( $qv( 'quote_amount', $r['quote_amount'] > 0 ? 0 + $r['quote_amount'] : '' ) ); ?>"<?php if ( $mine ) { sm_theme_aria( 'quote_amount' ); } ?>>
						<?php $err( 'quote_amount' ); ?>
					</div>
					<div class="sm-field">
						<label for="q-days-<?php echo esc_attr( $r['id'] ); ?>"><?php esc_html_e( 'Time needed (days)', 'sm-marketplace' ); ?></label>
						<input id="q-days-<?php echo esc_attr( $r['id'] ); ?>" name="quote_days" inputmode="numeric" dir="ltr" value="<?php echo esc_attr( $qv( 'quote_days', $r['quote_days'] > 0 ? (int) $r['quote_days'] : '' ) ); ?>"<?php if ( $mine ) { sm_theme_aria( 'quote_days' ); } ?>>
						<?php $err( 'quote_days' ); ?>
					</div>
				</div>
				<div class="sm-field">
					<label for="q-msg-<?php echo esc_attr( $r['id'] ); ?>"><?php esc_html_e( 'Message to the client', 'sm-marketplace' ); ?> <small class="sm-optional"><?php esc_html_e( 'optional', 'sm-marketplace' ); ?></small></label>
					<textarea id="q-msg-<?php echo esc_attr( $r['id'] ); ?>" name="quote_message" rows="3" dir="auto" maxlength="3000"><?php echo esc_textarea( $qv( 'quote_message', $r['quote_message'] ) ); ?></textarea>
					<?php $err( 'quote_message' ); ?>
				</div>
				<div class="smq-actions">
					<button class="sm-btn sm-btn--accent" type="submit" name="do" value="quote"><?php sm_icon( 'check' ); ?><?php esc_html_e( 'Send the quote', 'sm-marketplace' ); ?></button>
					<button class="sm-btn sm-btn--ghost" type="submit" name="do" value="decline" data-sm-confirm="<?php esc_attr_e( 'Decline this request? The client is told.', 'sm-marketplace' ); ?>"><?php esc_html_e( 'Decline the request', 'sm-marketplace' ); ?></button>
				</div>
			</form>
		</details>
	<?php elseif ( ! $recv && $open ) : ?>
		<div class="smq-actions">
			<?php
			$decide = static function ( $id, $do, $label, $class, $confirm = '' ) {
				sm_theme_form_open( 'service_decide', array( 'class' => 'smp-act' ) );
				echo '<input type="hidden" name="request_id" value="' . esc_attr( $id ) . '"><input type="hidden" name="do" value="' . esc_attr( $do ) . '">';
				echo '<button type="submit" class="sm-btn ' . esc_attr( $class ) . ' sm-btn--sm"' . ( $confirm ? ' data-sm-confirm="' . esc_attr( $confirm ) . '"' : '' ) . '>' . esc_html( $label ) . '</button></form>';
			};
			if ( 'quoted' === $r['status'] ) {
				$decide( $r['id'], 'accept', __( 'Accept the quote', 'sm-marketplace' ), 'sm-btn--accent' );
				$decide( $r['id'], 'decline', __( 'Decline the quote', 'sm-marketplace' ), 'sm-btn--ghost' );
			}
			$decide( $r['id'], 'cancel', __( 'Cancel the request', 'sm-marketplace' ), 'sm-btn--ghost', __( 'Cancel this request? The provider is told.', 'sm-marketplace' ) );
			?>
		</div>
	<?php endif; ?>
</li>
