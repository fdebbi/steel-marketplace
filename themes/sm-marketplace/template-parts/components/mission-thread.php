<?php
/**
 * Messages between the client (or the engineer) and the platform on a mission.
 *
 * @var array $args { mission: array (id, messages, can.write), intro: string }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;
$m = $args['mission'];
?>
<section class="sm-panel sme-thread" id="sme-messages">
	<h2 class="sm-panel__title"><?php esc_html_e( 'Messages with the platform', 'sm-marketplace' ); ?></h2>
	<?php if ( ! empty( $args['intro'] ) ) : ?><p class="sm-muted"><?php echo esc_html( $args['intro'] ); ?></p><?php endif; ?>
	<?php if ( $m['messages'] ) : ?>
		<ol class="sme-thread__list" role="list">
			<?php foreach ( $m['messages'] as $msg ) : ?>
				<li class="sme-msg<?php echo $msg['mine'] ? ' is-mine' : ''; ?><?php echo $msg['platform'] ? ' is-platform' : ''; ?>">
					<p class="sme-msg__who"><?php if ( $msg['platform'] ) : ?><?php sm_icon( 'shield' ); ?><?php endif; ?><strong><?php echo esc_html( $msg['author'] ); ?></strong> · <time datetime="<?php echo esc_attr( $msg['date'] ); ?>"><?php echo esc_html( sm_ago( $msg['date'] ) ); ?></time></p>
					<p class="sme-msg__body" dir="auto"><?php echo nl2br( esc_html( $msg['body'] ) ); ?></p>
				</li>
			<?php endforeach; ?>
		</ol>
	<?php endif; ?>
	<?php if ( ! empty( $m['can']['write'] ) ) : ?>
		<?php sm_theme_form_open( 'mission_message', array( 'class' => 'sm-form sme-thread__form' ) ); ?>
			<input type="hidden" name="mission_id" value="<?php echo (int) $m['id']; ?>">
			<label class="screen-reader-text" for="sme-body"><?php esc_html_e( 'Your message', 'sm-marketplace' ); ?></label>
			<textarea id="sme-body" name="body" rows="3" dir="auto" maxlength="5000" required placeholder="<?php esc_attr_e( 'Write to the platform…', 'sm-marketplace' ); ?>"<?php sm_theme_aria( 'body' ); ?>></textarea>
			<?php sm_theme_error( 'body' ); ?>
			<button class="sm-btn sm-btn--primary" type="submit"><?php sm_icon( 'message' ); ?><?php esc_html_e( 'Send', 'sm-marketplace' ); ?></button>
		</form>
	<?php endif; ?>
</section>
