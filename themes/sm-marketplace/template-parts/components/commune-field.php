<?php
/**
 * Commune chosen in the list of the wilaya (0.41.0). The list follows the
 * wilaya <select> (data-wilaya-select) and the map of the same form moves to
 * the chosen commune. A commune typed before the lists existed stays offered.
 *
 * @var array $args { name, id, wilaya, wilaya_select, value, label, required, optional, error, class }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$name  = (string) ( $args['name'] ?? 'commune' );
$id    = (string) ( $args['id'] ?? 'commune' );
$wil   = (string) ( $args['wilaya'] ?? '' );
$value = (string) ( $args['value'] ?? '' );
$all   = (array) sm_theme_data( 'communes' );
$is_ar = 0 === strpos( determine_locale(), 'ar' );
$list  = (array) ( $all[ $wil ] ?? array() );
$key   = static fn( $s ) => preg_replace( '/[^a-z0-9\x{0600}-\x{06FF}]+/u', '', mb_strtolower( remove_accents( (string) $s ) ) );
$found = '' === $value;
foreach ( $list as $c ) {
	if ( $key( $c[0] ) === $key( $value ) ) {
		$value = $c[0];
		$found = true;
		break;
	}
}
sm_theme_communes_data(); // The lists of all wilayas, once per page.
?>
<div class="<?php echo esc_attr( (string) ( $args['class'] ?? 'sm-field' ) ); ?>">
	<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $args['label'] ?? __( 'Commune', 'sm-marketplace' ) ); ?><?php echo ! empty( $args['required'] ) ? ' <span class="smx-req" aria-hidden="true">*</span>' : ''; ?><?php echo ! empty( $args['optional'] ) ? ' <small class="sm-optional">' . esc_html__( 'optional', 'sm-marketplace' ) . '</small>' : ''; ?></label>
	<select id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" data-sm-commune data-wilaya-select="<?php echo esc_attr( (string) ( $args['wilaya_select'] ?? '' ) ); ?>" data-empty="<?php esc_attr_e( 'Select', 'sm-marketplace' ); ?>" data-first="<?php esc_attr_e( 'Choose the wilaya first', 'sm-marketplace' ); ?>"<?php echo ! empty( $args['required'] ) ? ' aria-required="true"' : ''; ?><?php sm_theme_aria( (string) ( $args['error'] ?? $name ) ); ?>>
		<option value=""><?php echo esc_html( $wil ? __( 'Select', 'sm-marketplace' ) : __( 'Choose the wilaya first', 'sm-marketplace' ) ); ?></option>
		<?php if ( ! $found ) : ?><option value="<?php echo esc_attr( $value ); ?>" selected><?php echo esc_html( $value ); ?></option><?php endif; ?>
		<?php foreach ( $list as $c ) : ?><option value="<?php echo esc_attr( $c[0] ); ?>" data-lat="<?php echo esc_attr( (string) $c[2] ); ?>" data-lng="<?php echo esc_attr( (string) $c[3] ); ?>" <?php selected( $value, $c[0] ); ?>><?php echo esc_html( $is_ar ? $c[1] : $c[0] ); ?></option><?php endforeach; ?>
	</select>
	<?php sm_theme_error( (string) ( $args['error'] ?? $name ) ); ?>
</div>
