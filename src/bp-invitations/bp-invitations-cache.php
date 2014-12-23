<?php

/**
 * Functions related to invitations caching.
 *
 * @since BuddyPress (2.3.0)
 */

/**
 * Invalidate 'all_from_user_' and 'all_to_user_' caches when saving.
 *
 * @since BuddyPress (2.3.0)
 *
 * @param BP_Invitations_Invitation $n Invitation object.
 */
function bp_invitations_clear_user_caches_after_save( BP_Notifications_Notification $n ) {
	// User_id could be empty if a non-member is being invited via email.
	if ( ! empty( $n->user_id ) ) {
		wp_cache_delete( 'all_to_user_' . $n->user_id, 'bp_invitations' );
	}
	// Inviter_id could be empty if this is a request for membership.
	if ( ! empty( $n->inviter_id ) ) {
		wp_cache_delete( 'all_from_user_' . $n->inviter_id, 'bp_invitations' );
	}
}
add_action( 'bp_invitation_after_save', 'bp_invitations_clear_user_caches_after_save' );

/**
 * Invalidate 'all_from_user_' and 'all_to_user_' caches when 
 * updating or deleting.
 *
 * @since BuddyPress (2.3.0)
 *
 * @param int $args Invitation deletion arguments.
 */
function bp_invitations_clear_user_caches_before_update( $args ) {
	// Pull up a list of invitations matching the args (those about te be updated or deleted)
	$invites = BP_Invitations_Invitation::get( $args );

	$user_ids = array();
	$inviter_ids = array(); 
	foreach ( $invites as $i ) {
		$user_ids[] 	= $i->user_id;
		$inviter_ids[] 	= $i->inviter_id;
	}

	foreach ( array_unique( $user_ids ) as $user_id ) {
		wp_cache_delete( 'all_to_user_' . $user_id, 'bp_invitations' );
	}

	foreach ( array_unique( $inviter_ids ) as $inviter_id ) {
		wp_cache_delete( 'all_from_user_' . $inviter_id, 'bp_invitations' );
	}
}
add_action( 'bp_invitation_before_update', 'bp_invitations_clear_user_caches_before_update' );
add_action( 'bp_invitation_before_delete', 'bp_invitations_clear_user_caches_before_update' );