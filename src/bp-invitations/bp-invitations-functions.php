<?php

/**
 * BuddyPress Member Invitations Functions.
 *
 * Functions and filters used in the invitations component.
 *
 * @package BuddyPress
 * @subpackage InvitationsFunctions
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
	exit;
}


/** Create ********************************************************************/

/**
 * Add an invitation to a specific user, from a specific user, related to a 
 * specific component.
 *
 * @since BuddyPress (2.3.0)
 *
 * @param array $args {
 *     Array of arguments describing the notification. All are optional.
 *	   @type int $user_id ID of the invited user.
 *	   @type int $inviter_id ID of the user who created the invitation.
 *	   @type string $invitee_email Email address of the invited user.
 * 	   @type string $component_name Name of the related component.
 *	   @type string $component_action Name of the related component action.
 * 	   @type int item_id ID associated with the invitation and component.
 * 	   @type int secondary_item_id secondary ID associated with the 
 *			 invitation and component.
 * 	   @type string content Extra information provided by the requester 
 *			 or inviter.
 * 	   @type string date_modified Date the invitation was last modified.
 * 	   @type int invite_sent Has the invitation been sent, or is it a 
 *			 draft invite?
 * }
 * @return int|bool ID of the newly created invitation on success, false
 *         on failure.
 */
function bp_invitations_add_invitation( $args = array() ) {

	$r = wp_parse_args( $args, array(
		'user_id'           => 0,
		'inviter_id'		=> 0,
		'invitee_email'		=> '',
		'component_name'    => '',
		'component_action'  => '',
		'item_id'           => 0,
		'secondary_item_id' => 0,
		'content'			=> '',
		'date_modified'     => bp_core_current_time(),
		'invite_sent'       => 0,
	) );

	// If there is no invitee, bail.
	if ( empty( $r['user_id'] ) && empty( $r['invitee_email'] ) ) {
		return false;
	}

	if ( ! empty( $r['inviter_id'] ) ) {
		/**
		 * Is this user allowed to extend invitations from this component/item?
		 *
		 * @since BuddyPress (2.3.0)
		 *
		 * @param array $r Describes the invitation to be added. 
		 */
		if ( ! apply_filters( 'bp_invitations_inviter_can_send_invites', true, $r ) ) {
			return false;
		}
	} else {
		/**
		 * In the case of a request, is the item accepting requests? 
		 *
		 * @since BuddyPress (2.3.0)
		 *
		 * @param array $r Describes the invitation to be added. 
		 */
		if ( ! apply_filters( 'bp_invitations_item_is_accepting_requests', true, $r ) ) {
			return false;
		}
	}

	// Check for existing duplicate invitations
	$existing = BP_Notifications_Notification::get( array(
		'user_id'           => $r['user_id'],
		'inviter_id'        => $r['inviter_id'],
		'invitee_email'     => $r['invitee_email'],
		'component_name'    => $r['component_name'],
		'component_action'  => $r['component_action'],
		'item_id'           => $r['item_id'],
		'secondary_item_id' => $r['secondary_item_id'],
	) );

	if ( ! empty( $existing ) ) {
		return false;
	}
	
	// Set up the new invitation
	$invitation                    = new BP_Invitations_Invitation;
	$invitation->user_id           = $r['user_id'];
	$invitation->inviter_id        = $r['inviter_id'];
	$invitation->invitee_email     = $r['invitee_email'];
	$invitation->component_name    = $r['component_name'];
	$invitation->component_action  = $r['component_action'];
	$invitation->item_id           = $r['item_id'];
	$invitation->secondary_item_id = $r['secondary_item_id'];
	$invitation->date_modified     = $r['date_modified'];
	$invitation->invite_sent       = $r['invite_sent'];

	// Save the new invitation
	return $invitation->save();
}


/** Retrieve ******************************************************************/

/**
 * Get a specific invitation by its ID.
 *
 * @since BuddyPress (2.3.0)
 *
 * @param int $id ID of the invitation.
 * @return BP_Invitations_Invitation object
 */
function bp_invitations_get_invitation_by_id( $id ) {
	return new BP_Invitations_Invitation( $id );
}

/**
 * Get invitations, based on provided filter parameters.
 *
 * @since BuddyPress (2.3.0)
 *
 * @param array $args {
 *     Associative array of arguments. All arguments but $page and
 *     $per_page can be treated as filter values for get_where_sql()
 *     and get_query_clauses(). All items are optional.
 *     @type int|array $id ID of invitation being updated. Can be an
 *           array of IDs.
 *     @type int|array $user_id ID of user being queried. Can be an
 *           array of user IDs.
 *     @type int|array $inviter_id ID of user who created the invitation.
 *			 Can be an array of user IDs.
 *     @type string|array $invitee_email Email address of invited users
 *			 being queried. Can be an array of email addresses.
 *     @type string|array $component_name Name of the component to
 *           filter by. Can be an array of component names.
 *     @type string|array $component_action Name of the action to
 *           filter by. Can be an array of actions.
 *     @type int|array $item_id ID of associated item. Can be an array
 *           of multiple item IDs.
 *     @type int|array $secondary_item_id ID of secondary associated
 *           item. Can be an array of multiple IDs.
 *     @type string $invite_sent Limit to draft, sent or all invitations. 
 *			 'draft' returns only unsent invitations, 'sent' returns only 
 *			 sent invitations, 'all' returns all. Default: 'all'.
 *     @type string $search_terms Term to match against component_name
 *           or component_action fields.
 *     @type string $order_by Database column to order invitations by.
 *     @type string $sort_order Either 'ASC' or 'DESC'.
 *     @type string $order_by Field to order results by.
 *     @type string $sort_order ASC or DESC.
 *     @type int $page Number of the current page of results. Default:
 *           false (no pagination - all items).
 *     @type int $per_page Number of items to show per page. Default:
 *           false (no pagination - all items).
 * }
 * @return array Located invitations.
 */
function bp_invitations_get_invitations( $args ) {
	return BP_Invitations_Invitation::get( $args );
}

/**
 * Get "sent" incoming invitations for a user and cache them.
 *
 * @since BuddyPress (2.3.0)
 *
 * @param int $user_id ID of the user whose incoming invitations are being 
 * 		  fetched.
 * @return array Located invitations.
 */
function bp_invitations_get_incoming_invitations_for_user( $user_id = 0 ) {

	// Default to displayed user if no ID is passed
	if ( empty( $user_id ) ) {
		$user_id = ( bp_displayed_user_id() ) ? bp_displayed_user_id() : bp_loggedin_user_id();
	}

	// Get notifications out of the cache, or query if necessary
	$invitations = wp_cache_get( 'all_to_user_' . $user_id, 'bp_invitations' );
	if ( false === $invitations ) {
		$invitations = BP_Invitations_Invitation::get_sent_to_user( array(
			'user_id' => $user_id
		) );
		wp_cache_set( 'all_to_user_' . $user_id, $invitations, 'bp_$invitations' );
	}

	// Filter and return
	return apply_filters( 'bp_invitations_get_incoming_invitations_for_user', $invitations, $user_id );
}

/**
 * Get all outgoing invitations from a user and cache them.
 *
 * @since BuddyPress (2.3.0)
 *
 * @param int $user_id ID of the user whose incoming invitations are being 
 * 		  fetched.
 * @return array Located invitations.
 */
function bp_invitations_get_outgoing_invitations_for_user( $user_id = 0 ) {

	// Default to displayed user if no ID is passed
	if ( empty( $user_id ) ) {
		$user_id = ( bp_displayed_user_id() ) ? bp_displayed_user_id() : bp_loggedin_user_id();
	}

	// Get notifications out of the cache, or query if necessary
	$invitations = wp_cache_get( 'all_from_user_' . $user_id, 'bp_invitations' );
	if ( false === $invitations ) {
		$invitations = BP_Invitations_Invitation::get_all_from_user( array(
			'user_id' => $user_id
		) );
		wp_cache_set( 'all_from_user_' . $user_id, $invitations, 'bp_$invitations' );
	}

	// Filter and return
	return apply_filters( 'bp_invitations_get_incoming_invitations_for_user', $notifications, $user_id );
}

/** Update ********************************************************************/

/**
 * Update invitation, based on provided filter parameters.
 *
 * @since BuddyPress (2.3.0)
 *
 * @see BP_Invitations_Invitation::get() for a description of
 *      accepted update/where arguments.
 *
 * @param array $update_args Associative array of fields to update,
 *        and the values to update them to. Of the format
 *            array( 'user_id' => 4, 'component_name' => 'groups', )
 * @param array $where_args Associative array of columns/values, to
 *        determine which rows should be updated. Of the format
 *            array( 'item_id' => 7, 'component_action' => 'members', )
 * @return int|bool Number of rows updated on success, false on failure.
 */
function bp_invitations_update_invitation( $update_args = array(), $where_args = array() ) {
	//@TODO: access check
	// if ( ! bp_notifications_check_notification_access( bp_loggedin_user_id(), $id ) ) {
	// 	return false;
	// }

	return BP_Invitations_Invitation::mark_as_sent( $update_args = array(), $where_args = array() );
}

/**
 * Mark invitation as sent by invitation ID.
 *
 * @since BuddyPress (2.3.0)
 *
 * @param int $id The ID of the invitation to mark as sent.
 * @return bool True on success, false on failure.
 */
function bp_invitations_mark_as_sent( $id ) {
	//@TODO: access check
	// if ( ! bp_notifications_check_notification_access( bp_loggedin_user_id(), $id ) ) {
	// 	return false;
	// }

	return BP_Invitations_Invitation::mark_as_sent( $id );
}


/** Delete ********************************************************************/

/**
 * Delete a specific invitation by its ID.
 *
 * Used when rejecting invitations or membership requests. 
 *
 * @since BuddyPress (2.3.0)
 *
 * @param int $id ID of the invitation to delete.
 * @return bool True on success, false on failure.
 */
function bp_invitations_delete_invitation( $id ) {
	//@TODO: access check
	// if ( ! bp_notifications_check_notification_access( bp_loggedin_user_id(), $id ) ) {
	// 	return false;
	// }

	return BP_Invitations_Invitation::delete_by_id( $id );
}

/**
 * Delete all invitations by type.
 *
 * Used when clearing out invitations for an entire component. Possibly used
 * when deactivating a component that created invitations.
 *
 * @since BuddyPress (2.3.0)
 *
 * @param string $component_name Name of the associated component.
 * @param string $component_action Optional. Name of the associated action.
 * @return bool True on success, false on failure.
 */
function bp_invitations_delete_all_invitations_by_component( $component_name, $component_action = false ) {
	return BP_Notifications_Notification::delete( array(
		'component_name'    => $component_name,
		'component_action'  => $component_action,
	) );
}

/** Helpers *******************************************************************/

/**
 * Check if a user has access to a specific notification.
 *
 * Used before deleting a notification for a user.
 *
 * @since BuddyPress (1.9.0)
 *
 * @param int $user_id ID of the user being checked.
 * @param int $notification_id ID of the notification being checked.
 * @return bool True if the notification belongs to the user, otherwise false.
 */
// function bp_notifications_check_notification_access( $user_id, $notification_id ) {
// 	return (bool) BP_Notifications_Notification::check_access( $user_id, $notification_id );
// }

/**
 * Get a count of incoming invitations for a user.
 *
 * @since BuddyPress (2.3.0)
 *
 * @param int $user_id ID of the user whose incoming invitations are being
 *        counted.
 * @return int Unread notification count.
 */
function bp_invitations_get_incoming_invitation_count( $user_id = 0 ) {
	$invitations = bp_invitations_get_incoming_invitations_for_user( $user_id );
	$count         = ! empty( $invitations ) ? count( $invitations ) : 0;

	return apply_filters( 'bp_invitations_get_incoming_invitation_count', (int) $count );
}

/**
 * Return an array of component names that are currently active and have
 * registered Invitations callbacks.
 *
 * @since BuddyPress (2.3.0)
 *
 * @return array
 */
function bp_invitations_get_registered_components() {

	// Load BuddyPress
	$bp = buddypress();

	// Setup return value
	$component_names = array();

	// Get the active components
	$active_components = array_keys( $bp->active_components );

	// Loop through components, look for callbacks, add to return value
	foreach ( $active_components as $component ) {
		if ( !empty( $bp->$component->invitation_callback ) ) {
			$component_names[] = $component;
		}
	}

	// Return active components with registered notifications callbacks
	return apply_filters( 'bp_invitations_get_registered_components', $component_names, $active_components );
}
