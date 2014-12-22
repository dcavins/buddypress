<?php

/**
 * BuddyPress Member Invitations Loader.
 *
 * Initializes the Invitations component.
 *
 * @package BuddyPress
 * @subpackage InvitationsLoader
 * @since BuddyPress (2.3.0)
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { 
	exit;
}

class BP_Notifications_Component extends BP_Component {

	/**
	 * Start the invitations component creation process.
	 *
	 * @since BuddyPress (2.3.0)
	 */
	public function __construct() {
		parent::start(
			'invitations',
			_x( 'Notifications', 'Page <title>', 'buddypress' ),
			buddypress()->plugin_dir,
			array(
				'adminbar_myaccount_order' => 30
			)
		);
	}

	/**
	 * Include invitations component files.
	 *
	 * @since BuddyPress (2.3.0)
	 *
	 * @see BP_Component::includes() for a description of arguments.
	 *
	 * @param array $includes See BP_Component::includes() for a description.
	 */
	public function includes( $includes = array() ) {
		$includes = array(
			'actions',
			'classes',
			'screens',
			'adminbar',
			'template',
			'functions',
			'cache',
		);

		parent::includes( $includes );
	}

	/**
	 * Set up component global data.
	 *
	 * @since BuddyPress (2.3.0)
	 *
	 * @see BP_Component::setup_globals() for a description of arguments.
	 *
	 * @param array $args See BP_Component::setup_globals() for a description.
	 */
	public function setup_globals( $args = array() ) {
		// Define a slug, if necessary
		if ( !defined( 'BP_NOTIFICATIONS_SLUG' ) ) {
			define( 'BP_NOTIFICATIONS_SLUG', $this->id );
		}

		// Global tables for the invitations component
		$global_tables = array(
			'table_name' => bp_core_get_table_prefix() . 'bp_invitations'
		);

		// All globals for the invitations component.
		// Note that global_tables is included in this array.
		$args = array(
			'slug'          => BP_NOTIFICATIONS_SLUG,
			'has_directory' => false,
			'search_string' => __( 'Search Notifications...', 'buddypress' ),
			'global_tables' => $global_tables,
		);

		parent::setup_globals( $args );
	}

	/**
	 * Set up component navigation.
	 *
	 * @since BuddyPress (2.3.0)
	 *
	 * @see BP_Component::setup_nav() for a description of arguments.
	 *
	 * @param array $main_nav Optional. See BP_Component::setup_nav() for
	 *        description.
	 * @param array $sub_nav Optional. See BP_Component::setup_nav() for
	 *        description.
	 */
	public function setup_nav( $main_nav = array(), $sub_nav = array() ) {

		// Only grab count if we're on a user page and current user has access
		if ( bp_is_user() && bp_user_has_access() ) {
			$count    = bp_invitations_get_unread_notification_count( bp_displayed_user_id() );
			$class    = ( 0 === $count ) ? 'no-count' : 'count';
			$nav_name = sprintf( _x( 'Notifications <span class="%s">%s</span>', 'Profile screen nav', 'buddypress' ), esc_attr( $class ), number_format_i18n( $count ) );
		} else {
			$nav_name = _x( 'Notifications', 'Profile screen nav', 'buddypress' );
		}

		// Add 'Notifications' to the main navigation
		$main_nav = array(
			'name'                    => $nav_name,
			'slug'                    => $this->slug,
			'position'                => 30,
			'show_for_displayed_user' => bp_core_can_edit_settings(),
			'screen_function'         => 'bp_invitations_screen_unread',
			'default_subnav_slug'     => 'unread',
			'item_css_id'             => $this->id,
		);

		// Determine user to use
		if ( bp_displayed_user_domain() ) {
			$user_domain = bp_displayed_user_domain();
		} elseif ( bp_loggedin_user_domain() ) {
			$user_domain = bp_loggedin_user_domain();
		} else {
			return;
		}

		$invitations_link = trailingslashit( $user_domain . bp_get_invitations_slug() );

		// Add the subnav items to the invitations nav item
		$sub_nav[] = array(
			'name'            => _x( 'Unread', 'Notification screen nav', 'buddypress' ),
			'slug'            => 'unread',
			'parent_url'      => $invitations_link,
			'parent_slug'     => bp_get_invitations_slug(),
			'screen_function' => 'bp_invitations_screen_unread',
			'position'        => 10,
			'item_css_id'     => 'invitations-my-invitations',
			'user_has_access' => bp_core_can_edit_settings(),
		);

		$sub_nav[] = array(
			'name'            => _x( 'Read', 'Notification screen nav', 'buddypress' ),
			'slug'            => 'read',
			'parent_url'      => $invitations_link,
			'parent_slug'     => bp_get_invitations_slug(),
			'screen_function' => 'bp_invitations_screen_read',
			'position'        => 20,
			'user_has_access' => bp_core_can_edit_settings(),
		);

		parent::setup_nav( $main_nav, $sub_nav );
	}

	/**
	 * Set up the component entries in the WordPress Admin Bar.
	 *
	 * @since BuddyPress (2.3.0)
	 *
	 * @see BP_Component::setup_nav() for a description of the $wp_admin_nav
	 *      parameter array.
	 *
	 * @param array $wp_admin_nav See BP_Component::setup_admin_bar() for a
	 *        description.
	 */
	public function setup_admin_bar( $wp_admin_nav = array() ) {

		// Menus for logged in user
		if ( is_user_logged_in() ) {

			// Setup the logged in user variables
			$invitations_link = trailingslashit( bp_loggedin_user_domain() . $this->slug );

			// Pending notification requests
			$count = bp_invitations_get_unread_notification_count( bp_loggedin_user_id() );
			if ( ! empty( $count ) ) {
				$title  = sprintf( _x( 'Notifications <span class="count">%s</span>', 'My Account Notification pending', 'buddypress' ), number_format_i18n( $count ) );
				$unread = sprintf( _x( 'Unread <span class="count">%s</span>', 'My Account Notification pending', 'buddypress' ), number_format_i18n( $count ) );
			} else {
				$title  = _x( 'Notifications', 'My Account Notification', 'buddypress' );
				$unread = _x( 'Unread', 'My Account Notification sub nav', 'buddypress' );
			}

			// Add the "My Account" sub menus
			$wp_admin_nav[] = array(
				'parent' => buddypress()->my_account_menu_id,
				'id'     => 'my-account-' . $this->id,
				'title'  => $title,
				'href'   => trailingslashit( $invitations_link ),
			);

			// Unread
			$wp_admin_nav[] = array(
				'parent' => 'my-account-' . $this->id,
				'id'     => 'my-account-' . $this->id . '-unread',
				'title'  => $unread,
				'href'   => trailingslashit( $invitations_link ),
			);

			// Read
			$wp_admin_nav[] = array(
				'parent' => 'my-account-' . $this->id,
				'id'     => 'my-account-' . $this->id . '-read',
				'title'  => __( 'Read', 'buddypress' ),
				'href'   => trailingslashit( $invitations_link . 'read' ),
			);
		}

		parent::setup_admin_bar( $wp_admin_nav );
	}

	/**
	 * Set up the title for pages and <title>.
	 *
	 * @since BuddyPress (2.3.0)
	 */
	public function setup_title() {
		$bp = buddypress();

		// Adjust title
		if ( bp_is_invitations_component() ) {
			if ( bp_is_my_profile() ) {
				$bp->bp_options_title = __( 'Notifications', 'buddypress' );
			} else {
				$bp->bp_options_avatar = bp_core_fetch_avatar( array(
					'item_id' => bp_displayed_user_id(),
					'type'    => 'thumb',
					'alt'     => sprintf( __( 'Profile picture of %s', 'buddypress' ), bp_get_displayed_user_fullname() )
				) );
				$bp->bp_options_title = bp_get_displayed_user_fullname();
			}
		}

		parent::setup_title();
	}
}

/**
 * Bootstrap the Notifications component.
 *
 * @since BuddyPress (2.3.0)
 */
function bp_setup_invitations() {
	buddypress()->invitations = new BP_Notifications_Component();
}
add_action( 'bp_setup_components', 'bp_setup_invitations', 6 );
