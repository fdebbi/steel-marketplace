<?php
/**
 * "My service requests": requests the company sent to providers.
 *
 * @var array $args { account: array }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

sm_component( 'service-requests-list', array( 'box' => 'sent' ) );
