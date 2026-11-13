<?php
/**
 * "My visits": visit requests the company sent to sellers (0.31.0).
 *
 * @var array $args { account: array }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

sm_component( 'visits-list', array( 'box' => 'sent' ) );
