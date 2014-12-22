<?php

/**
 * BuddyPress Notifications Screen Functions.
 *
 * Screen functions are the controllers of BuddyPress. They will execute when
 * their specific URL is caught. They will first save or manipulate data using
 * business functions, then pass on the user to a template file.
 *
 * @package BuddyPress
 * @subpackage InvitationsScreens
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Catch and route the outgoing invitations screen.
 *
 * @since BuddyPress (2.3.0)
 */
function bp_invitations_screen_outgoing() {
	do_action( 'bp_invitations_screen_outgoing' );

	bp_core_load_template( apply_filters( 'bp_invitations_template_outgoing', 'members/single/home' ) );
}

/**
 * Catch and route the incoming invitations screen.
 *
 * @since BuddyPress (2.3.0)
 */
function bp_invitations_screen_incoming() {
	do_action( 'bp_invitations_screen_incoming' );

	bp_core_load_template( apply_filters( 'bp_invitations_template_incoming', 'members/single/home' ) );
}

/**
 * Catch and route the 'settings' invitations screen.
 *
 * @since BuddyPress (2.3.0)
 */
function bp_invitations_screen_settings() {

}
