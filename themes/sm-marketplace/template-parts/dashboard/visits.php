<?php
/**
 * "Visit requests": requests received on the company's listings (0.31.0).
 *
 * @var array $args { account: array }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

sm_component( 'visits-list', array( 'box' => 'received' ) );
