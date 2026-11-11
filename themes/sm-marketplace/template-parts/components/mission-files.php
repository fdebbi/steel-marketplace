<?php
/**
 * Files of a mission (private links served by SM Engineering).
 *
 * @var array $args { files: array, title: string, empty?: string, remove?: int mission id (engineer can remove deliverables) }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;
$files = (array) ( $args['files'] ?? array() );
?>
<div class="sme-files">
	<?php if ( ! empty( $args['title'] ) ) : ?><h3 class="sm-panel__subtitle"><?php echo esc_html( $args['title'] ); ?></h3><?php endif; ?>
	<?php if ( $files ) : ?>
		<ul class="sme-files__list" role="list">
			<?php foreach ( $files as $f ) : ?>
				<li>
					<a href="<?php echo esc_url( $f['url'] ); ?>" target="_blank" rel="noopener"><?php sm_icon( $f['image'] ? 'image' : 'file' ); ?><span dir="auto"><?php echo esc_html( $f['name'] ); ?></span></a>
					<small><?php echo esc_html( $f['size'] ); ?> · <?php echo esc_html( sm_ago( $f['date'] ) ); ?></small>
					<?php if ( ! empty( $args['remove'] ) ) : ?>
						<?php sm_theme_form_open( 'mission_engineer', array( 'class' => 'sme-files__remove' ) ); ?>
							<input type="hidden" name="mission_id" value="<?php echo (int) $args['remove']; ?>">
							<input type="hidden" name="file_id" value="<?php echo (int) $f['id']; ?>">
							<button class="sm-icon-btn" name="do" value="remove" type="submit"><?php sm_icon( 'trash' ); ?><span class="screen-reader-text"><?php esc_html_e( 'Remove', 'sm-marketplace' ); ?></span></button>
						</form>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php elseif ( ! empty( $args['empty'] ) ) : ?>
		<p class="sm-muted"><?php echo esc_html( $args['empty'] ); ?></p>
	<?php endif; ?>
</div>
