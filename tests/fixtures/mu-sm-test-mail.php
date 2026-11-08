<?php
// TEST ENVIRONMENT ONLY: write e-mails to wp-content/sm-mails.jsonl instead of sending them.
add_filter( 'pre_wp_mail', static function ( $null, $atts ) {
	file_put_contents( WP_CONTENT_DIR . '/sm-mails.jsonl', wp_json_encode( array( 'to' => $atts['to'], 'subject' => $atts['subject'], 'message' => $atts['message'], 'headers' => $atts['headers'] ) ) . "\n", FILE_APPEND );
	return true;
}, 10, 2 );
