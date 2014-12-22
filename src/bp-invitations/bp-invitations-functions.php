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

/**
 * Get a specific invitation by its ID.
 *
 * @since BuddyPress (2.3.0)
 *
 * @param int $id ID of the invitation.
 * @return BP_Invitations_Invitation
 */
function bp_invitations_get_invitation( $id ) {
	return new BP_Invitations_Invitation( $id );
}

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

/**
 * Get "sent" incoming invitations for a user and cache them.
 *
 * @since BuddyPress (2.3.0)
 *
 * @param int $user_id ID of the user whose incoming invitations are being 
 * 		  fetched.
 * @return array
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
 * @return array
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

// /**
//  * Get notifications for a specific user.
//  *
//  * @since BuddyPress (1.9.0)
//  *
//  * @param int $user_id ID of the user whose notifications are being fetched.
//  * @param string $format Format of the returned values. 'string' returns HTML,
//  *        while 'object' returns a structured object for parsing.
//  * @return mixed Object or array on success, false on failure.
//  */
// function bp_notifications_get_notifications_for_user( $user_id, $format = 'string' ) {

// 	// Setup local variables
// 	$bp = buddypress();

// 	// Get notifications (out of the cache, or query if necessary)
// 	$notifications         = bp_invitations_get_incoming_invitations_for_user( $user_id );
// 	$grouped_notifications = array(); // Notification groups
// 	$renderable            = array(); // Renderable notifications

// 	// Group notifications by component and component_action and provide totals
// 	for ( $i = 0, $count = count( $notifications ); $i < $count; ++$i ) {
// 		$notification = $notifications[$i];
// 		$grouped_notifications[$notification->component_name][$notification->component_action][] = $notification;
// 	}

// 	// Bail if no notification groups
// 	if ( empty( $grouped_notifications ) ) {
// 		return false;
// 	}

// 	// Calculate a renderable output for each notification type
// 	foreach ( $grouped_notifications as $component_name => $action_arrays ) {

// 		// Skip if group is empty
// 		if ( empty( $action_arrays ) ) {
// 			continue;
// 		}

// 		// Loop through each actionable item and try to map it to a component
// 		foreach ( (array) $action_arrays as $component_action_name => $component_action_items ) {

// 			// Get the number of actionable items
// 			$action_item_count = count( $component_action_items );

// 			// Skip if the count is less than 1
// 			if ( $action_item_count < 1 ) {
// 				continue;
// 			}

// 			// Callback function exists
// 			if ( isset( $bp->{$component_name}->notification_callback ) && is_callable( $bp->{$component_name}->notification_callback ) ) {

// 				// Function should return an object
// 				if ( 'object' === $format ) {

// 					// Retrieve the content of the notification using the callback
// 					$content = call_user_func(
// 						$bp->{$component_name}->notification_callback,
// 						$component_action_name,
// 						$component_action_items[0]->item_id,
// 						$component_action_items[0]->secondary_item_id,
// 						$action_item_count,
// 						'array'
// 					);

// 					// Create the object to be returned
// 					$notification_object = $component_action_items[0];

// 					// Minimal backpat with non-compatible notification
// 					// callback functions
// 					if ( is_string( $content ) ) {
// 						$notification_object->content = $content;
// 						$notification_object->href    = bp_loggedin_user_domain();
// 					} else {
// 						$notification_object->content = $content['text'];
// 						$notification_object->href    = $content['link'];
// 					}

// 					$renderable[] = $notification_object;

// 				// Return an array of content strings
// 				} else {
// 					$content      = call_user_func( $bp->{$component_name}->notification_callback, $component_action_name, $component_action_items[0]->item_id, $component_action_items[0]->secondary_item_id, $action_item_count );
// 					$renderable[] = $content;
// 				}

// 			// @deprecated format_notification_function - 1.5
// 			} elseif ( isset( $bp->{$component_name}->format_notification_function ) && function_exists( $bp->{$component_name}->format_notification_function ) ) {
// 				$renderable[] = call_user_func( $bp->{$component_name}->format_notification_function, $component_action_name, $component_action_items[0]->item_id, $component_action_items[0]->secondary_item_id, $action_item_count );

// 			// Allow non BuddyPress components to hook in
// 			} else {

// 				// The array to reference with apply_filters_ref_array()
// 				$ref_array = array(
// 					$component_action_name,
// 					$component_action_items[0]->item_id,
// 					$component_action_items[0]->secondary_item_id,
// 					$action_item_count,
// 					$format
// 				);

// 				// Function should return an object
// 				if ( 'object' === $format ) {

// 					// Retrieve the content of the notification using the callback
// 					$content = apply_filters_ref_array( 'bp_notifications_get_notifications_for_user', $ref_array );

// 					// Create the object to be returned
// 					$notification_object = $component_action_items[0];

// 					// Minimal backpat with non-compatible notification
// 					// callback functions
// 					if ( is_string( $content ) ) {
// 						$notification_object->content = $content;
// 						$notification_object->href    = bp_loggedin_user_domain();
// 					} else {
// 						$notification_object->content = $content['text'];
// 						$notification_object->href    = $content['link'];
// 					}

// 					$renderable[] = $notification_object;

// 				// Return an array of content strings
// 				} else {
// 					$renderable[] = apply_filters_ref_array( 'bp_notifications_get_notifications_for_user', $ref_array );
// 				}
// 			}
// 		}
// 	}

// 	// If renderable is empty array, set to false
// 	if ( empty( $renderable ) ) {
// 		$renderable = false;
// 	}

// 	// Filter and return
// 	return apply_filters( 'bp_core_get_notifications_for_user', $renderable, $user_id, $format );
// }

/** Delete ********************************************************************/

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
