<?php

/**
 * BuddyPress Invitations Classes
 *
 * Classes used for the Invitations component.
 *
 * @package BuddyPress
 * @subpackage InvitationsClasses
 *
 * @since BuddyPress (2.3.0)
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * BuddyPress Invitations.
 *
 * Use this class to create, access, edit, or delete BuddyPress Invitations.
 *
 * @since BuddyPress (2.3.0)
 */
class BP_Invitations_Invitation {

	/**
	 * The invitation ID.
	 *
	 * @since BuddyPress (2.3.0)
	 * @access public
	 * @var int
	 */
	public $id;

	/**
	 * The ID of the invited user.
	 *
	 * @since BuddyPress (2.3.0)
	 * @access public
	 * @var int
	 */
	public $user_id;

	/**
	 * The ID of the user who created the invitation.
	 *
	 * @since BuddyPress (2.3.0)
	 * @access public
	 * @var int
	 */
	public $inviter_id;

	/**
	 * The email address of the invited user.
	 * Used when extending an invitation to someone who does not belong to the site.
	 *
	 * @since BuddyPress (2.3.0)
	 * @access public
	 * @var string
	 */
	public $invitee_email;

	/**
	 * The name of the related component.
	 *
	 * @since BuddyPress (2.3.0)
	 * @access public
	 * @var string
	 */
	public $component_name;

	/**
	 * The name of the related component action.
	 *
	 * @since BuddyPress (2.3.0)
	 * @access public
	 * @var string
	 */
	public $component_action;

	/**
	 * The ID associated with the invitation and component.
	 * Example: the group ID if a group invitation
	 *
	 * @since BuddyPress (2.3.0)
	 * @access public
	 * @var int
	 */
	public $item_id;

	/**
	 * The secondary ID associated with the invitation and component.
	 * Example: a taxonomy term ID if invited to a site's category-specific RSS feed 
	 *
	 * @since BuddyPress (2.3.0)
	 * @access public
	 * @var int
	 */
	public $secondary_item_id = null;

	/**
	 * Extra information provided by the requester or inviter.
	 *
	 * @since BuddyPress (2.3.0)
	 * @access public
	 * @var string
	 */
	public $content;

	/**
	 * The date the invitation was last modified.
	 *
	 * @since BuddyPress (2.3.0)
	 * @access public
	 * @var string
	 */
	public $date_modified;

	/**
	 * Has the invitation been sent, or is it a draft invite?
	 *
	 * @since BuddyPress (2.3.0)
	 * @access public
	 * @var bool
	 */
	public $invite_sent;

	/** Public Methods ****************************************************/

	/**
	 * Constructor method.
	 *
	 * @since BuddyPress (2.3.0)
	 *
	 * @param int $id Optional. Provide an ID to access an existing
	 *        invitation item.
	 */
	public function __construct( $id = 0 ) {
		if ( ! empty( $id ) ) {
			$this->id = $id;
			$this->populate();
		}
	}

	/**
	 * Update or insert invitation details into the database.
	 *
	 * @since BuddyPress (2.3.0)
	 *
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function save() {

		// Return value
		$retval = false;

		// Default data and format
		$data = array(
			'user_id'           => $this->user_id,
			'inviter_id'		=> $this->inviter_id,
			'invitee_email'		=> $this->invitee_email,
			'component_name'    => $this->component_name,
			'component_action'  => $this->component_action,
			'item_id'           => $this->item_id,
			'secondary_item_id' => $this->secondary_item_id,
			'content'			=> $this->content,
			'date_modified'     => $this->date_modified,
			'invite_sent'       => $this->invite_sent,
		);
		$data_format = array( '%d', '%d', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%d' );

		do_action_ref_array( 'bp_invitation_before_save', array( &$this ) );

		// Update
		if ( ! empty( $this->id ) ) {
			$result = self::_update( $data, array( 'ID' => $this->id ), $data_format, array( '%d' ) );

		// Insert
		} else {
			$result = self::_insert( $data, $data_format );
		}

		// Set the invitation ID if successful
		if ( ! empty( $result ) && ! is_wp_error( $result ) ) {
			global $wpdb;

			$this->id = $wpdb->insert_id;
			$retval   = $wpdb->insert_id;
		}

		do_action_ref_array( 'bp_invitation_after_save', array( &$this ) );

		// Return the result
		return $retval;
	}

	/**
	 * Fetch data for an existing invitation from the database.
	 *
	 * @since BuddyPress (2.3.0)
	 *
	 * @global BuddyPress $bp The one true BuddyPress instance.
	 * @global wpdb $wpdb WordPress database object.
	 */
	public function populate() {
		global $wpdb;

		$bp = buddypress();

		// Fetch the invitation
		$invitation = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$bp->invitations->table_name} WHERE id = %d", $this->id ) );

		// Set up the invitation data
		if ( ! empty( $invitation ) && ! is_wp_error( $invitation ) ) {
			$this->user_id          	=> $invitation->user_id,
			$this->inviter_id			=> $invitation->inviter_id,
			$this->invitee_email		=> $invitation->invitee_email,
			$this->component_name    	=> $invitation->component_name,
			$this->component_action  	=> $invitation->component_action,
			$this->item_id           	=> $invitation->item_id,
			$this->secondary_item_id 	=> $invitation->secondary_item_id,
			$this->content				=> $invitation->content,
			$this->date_modified     	=> $invitation->date_modified,
			$this->invite_sent       	=> $invitation->invite_sent,
		}
	}

	/** Protected Static Methods ******************************************/

	/**
	 * Create a invitation entry.
	 *
	 * @since BuddyPress (2.3.0)
	 *
	 * @param array $data {
	 *     Array of invitation data, passed to {@link wpdb::insert()}.
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
	 * @param array $data_format See {@link wpdb::insert()}.
	 * @return int|false The number of rows inserted, or false on error.
	 */
	protected static function _insert( $data = array(), $data_format = array() ) {
		global $wpdb;
		return $wpdb->insert( buddypress()->invitations->table_name, $data, $data_format );
	}

	/**
	 * Update invitations.
	 *
	 * @since BuddyPress (2.3.0)
	 *
	 * @see wpdb::update() for further description of paramater formats.
	 *
	 * @param array $data Array of invitation data to update, passed to
	 *        {@link wpdb::update()}. Accepts any property of a
	 *        BP_Invitations_Invitation object.
	 * @param array $where The WHERE params as passed to wpdb::update().
	 *        Typically consists of array( 'ID' => $id ) to specify the ID
	 *        of the item being updated. See {@link wpdb::update()}.
	 * @param array $data_format See {@link wpdb::insert()}.
	 * @param array $where_format See {@link wpdb::insert()}.
	 * @return int|false The number of rows updated, or false on error.
	 */
	protected static function _update( $data = array(), $where = array(), $data_format = array(), $where_format = array() ) {
		global $wpdb;
		return $wpdb->update( buddypress()->invitations->table_name, $data, $where, $data_format, $where_format );
	}

	/**
	 * Delete invitations.
	 *
	 * @since BuddyPress (2.3.0)
	 *
	 * @see wpdb::update() for further description of paramater formats.
	 *
	 * @param array $where Array of WHERE clauses to filter by, passed to
	 *        {@link wpdb::delete()}. Accepts any property of a
	 *        BP_Invitations_Invitation object.
	 * @param array $where_format See {@link wpdb::insert()}.
	 * @return int|false The number of rows updated, or false on error.
	 */
	protected static function _delete( $where = array(), $where_format = array() ) {
		global $wpdb;
		return $wpdb->delete( buddypress()->invitations->table_name, $where, $where_format );
	}

	/**
	 * Assemble the WHERE clause of a get() SQL statement.
	 *
	 * Used by BP_Invitations_Invitation::get() to create its WHERE
	 * clause.
	 *
	 * @since BuddyPress (2.3.0)
	 *
	 * @param array $args See {@link BP_Invitations_Invitation::get()}
	 *        for more details.
	 * @return string WHERE clause.
	 */
	protected static function get_where_sql( $args = array() ) {
		global $wpdb;

		$where_conditions = array();
		$where            = '';

		// id
		if ( ! empty( $args['id'] ) ) {
			$id_in = implode( ',', wp_parse_id_list( $args['id'] ) );
			$where_conditions['id'] = "id IN ({$id_in})";
		}

		// user_id
		if ( ! empty( $args['user_id'] ) ) {
			$user_id_in = implode( ',', wp_parse_id_list( $args['user_id'] ) );
			$where_conditions['user_id'] = "user_id IN ({$user_id_in})";
		}

		// inviter_id
		if ( ! empty( $args['inviter_id'] ) ) {
			$inviter_id_in = implode( ',', wp_parse_id_list( $args['inviter_id'] ) );
			$where_conditions['inviter_id'] = "inviter_id IN ({$inviter_id_in})";
		}

		// invitee_email
		if ( ! empty( $args['invitee_email'] ) ) {
			if ( ! is_array( $args['invitee_email'] ) ) {
				$invitee_emails = explode( ',', $args['invitee_email'] );
			} else {
				$invitee_emails = $args['invitee_email'];
			}

			$email_clean = array();
			foreach ( $invitee_emails as $email ) {
				$email_clean[] = $wpdb->prepare( '%s', $email );
			}

			$invitee_email_in = implode( ',', $email_clean );
			$where_conditions['invitee_email'] = "invitee_email IN ({$invitee_email_in})";
		}

		// component_name
		if ( ! empty( $args['component_name'] ) ) {
			if ( ! is_array( $args['component_name'] ) ) {
				$component_names = explode( ',', $args['component_name'] );
			} else {
				$component_names = $args['component_name'];
			}

			$cn_clean = array();
			foreach ( $component_names as $cn ) {
				$cn_clean[] = $wpdb->prepare( '%s', $cn );
			}

			$cn_in = implode( ',', $cn_clean );
			$where_conditions['component_name'] = "component_name IN ({$cn_in})";
		}

		// component_action
		if ( ! empty( $args['component_action'] ) ) {
			if ( ! is_array( $args['component_action'] ) ) {
				$component_actions = explode( ',', $args['component_action'] );
			} else {
				$component_actions = $args['component_action'];
			}

			$ca_clean = array();
			foreach ( $component_actions as $ca ) {
				$ca_clean[] = $wpdb->prepare( '%s', $ca );
			}

			$ca_in = implode( ',', $ca_clean );
			$where_conditions['component_action'] = "component_action IN ({$ca_in})";
		}

		// item_id
		if ( ! empty( $args['item_id'] ) ) {
			$item_id_in = implode( ',', wp_parse_id_list( $args['item_id'] ) );
			$where_conditions['item_id'] = "item_id IN ({$item_id_in})";
		}

		// secondary_item_id
		if ( ! empty( $args['secondary_item_id'] ) ) {
			$secondary_item_id_in = implode( ',', wp_parse_id_list( $args['secondary_item_id'] ) );
			$where_conditions['secondary_item_id'] = "secondary_item_id IN ({$secondary_item_id_in})";
		}

		// invite_sent
		// Only create a where statement if something less than "all" has been
		// specifically requested.
		if ( ! empty( $args['invite_sent'] ) && 'all' !== $args['invite_sent'] ) {
			if ( args['invite_sent'] == 'draft' ) {
				$where_conditions['invite_sent'] = "invite_sent = 0";
			} else if ( args['invite_sent'] == 'sent' ) {
				$where_conditions['invite_sent'] = "invite_sent = 1";
			}
		}

		// search_terms
		if ( ! empty( $args['search_terms'] ) ) {
			$search_terms_like = '%' . bp_esc_like( $args['search_terms'] ) . '%';
			$where_conditions['search_terms'] = $wpdb->prepare( "( component_name LIKE %s OR component_action LIKE %s )", $search_terms_like, $search_terms_like );
		}

		// Custom WHERE
		if ( ! empty( $where_conditions ) ) {
			$where = 'WHERE ' . implode( ' AND ', $where_conditions );
		}

		return $where;
	}

	/**
	 * Assemble the ORDER BY clause of a get() SQL statement.
	 *
	 * Used by BP_Invitations_Invitation::get() to create its ORDER BY
	 * clause.
	 *
	 * @since BuddyPress (2.3.0)
	 *
	 * @param array $args See {@link BP_Invitations_Invitation::get()}
	 *        for more details.
	 * @return string ORDER BY clause.
	 */
	protected static function get_order_by_sql( $args = array() ) {

		// Setup local variable
		$conditions = array();
		$retval     = '';

		// Order by
		if ( ! empty( $args['order_by'] ) ) {
			$order_by               = implode( ', ', (array) $args['order_by'] );
			$conditions['order_by'] = "{$order_by}";
		}

		// Sort order direction
		if ( ! empty( $args['sort_order'] ) && in_array( $args['sort_order'], array( 'ASC', 'DESC' ) ) ) {
			$sort_order               = $args['sort_order'];
			$conditions['sort_order'] = "{$sort_order}";
		}

		// Custom ORDER BY
		if ( ! empty( $conditions ) ) {
			$retval = 'ORDER BY ' . implode( ' ', $conditions );
		}

		return $retval;
	}

	/**
	 * Assemble the LIMIT clause of a get() SQL statement.
	 *
	 * Used by BP_Invitations_Invitation::get() to create its LIMIT clause.
	 *
	 * @since BuddyPress (2.3.0)
	 *
	 * @param array $args See {@link BP_Invitations_Invitation::get()}
	 *        for more details.
	 * @return string LIMIT clause.
	 */
	protected static function get_paged_sql( $args = array() ) {
		global $wpdb;

		// Setup local variable
		$retval = '';

		// Custom LIMIT
		if ( ! empty( $args['page'] ) && ! empty( $args['per_page'] ) ) {
			$page     = absint( $args['page']     );
			$per_page = absint( $args['per_page'] );
			$offset   = $per_page * ( $page - 1 );
			$retval   = $wpdb->prepare( "LIMIT %d, %d", $offset, $per_page );
		}

		return $retval;
	}

	/**
	 * Assemble query clauses, based on arrguments, to pass to $wpdb methods.
	 *
	 * The insert(), update(), and delete() methods of {@link wpdb} expect
	 * arguments of the following forms:
	 *
	 * - associative arrays whose key/value pairs are column => value, to
	 *   be used in WHERE, SET, or VALUES clauses
	 * - arrays of "formats", which tell $wpdb->prepare() which type of
	 *   value to expect when sanitizing (eg, array( '%s', '%d' ))
	 *
	 * This utility method can be used to assemble both kinds of params,
	 * out of a single set of associative array arguments, such as:
	 *
	 *     $args = array(
	 *         'user_id' => 4,
	 *         'component_name' => 'groups',
	 *     );
	 *
	 * This will be converted to:
	 *
	 *     array(
	 *         'data' => array(
	 *             'user_id' => 4,
	 *             'component_name' => 'groups',
	 *         ),
	 *         'format' => array(
	 *             '%d',
	 *             '%s',
	 *         ),
	 *     )
	 *
	 * which can easily be passed as arguments to the $wpdb methods.
	 *
	 * @since BuddyPress (2.3.0)
	 *
	 * @param $args Associative array of filter arguments.
	 *        See {@BP_Invitations_Invitation::get()} for a breakdown.
	 * @return array Associative array of 'data' and 'format' args.
	 */
	protected static function get_query_clauses( $args = array() ) {
		$where_clauses = array(
			'data'   => array(),
			'format' => array(),
		);


			'user_id'           => $this->user_id,
			'inviter_id'		=> $this->inviter_id,
			'invitee_email'		=> $this->invitee_email,
			'component_name'    => $this->component_name,
			'component_action'  => $this->component_action,
			'item_id'           => $this->item_id,
			'secondary_item_id' => $this->secondary_item_id,
			'content'			=> $this->content,
			'date_modified'     => $this->date_modified,
			'invite_sent'       => $this->invite_sent,


		// id
		if ( ! empty( $args['id'] ) ) {
			$where_clauses['data']['id'] = absint( $args['id'] );
			$where_clauses['format'][] = '%d';
		}

		// user_id
		if ( ! empty( $args['user_id'] ) ) {
			$where_clauses['data']['user_id'] = (int) $args['user_id'];
			$where_clauses['format'][] = '%d';
		}
		
		// inviter_id
		if ( ! empty( $args['inviter_id'] ) ) {
			$where_clauses['data']['inviter_id'] = (int) $args['inviter_id'];
			$where_clauses['format'][] = '%d';
		}

		// invitee_email
		if ( ! empty( $args['invitee_email'] ) ) {
			$where_clauses['data']['invitee_email'] = $args['invitee_email'];
			$where_clauses['format'][] = '%s';
		}

		// component_name
		if ( ! empty( $args['component_name'] ) ) {
			$where_clauses['data']['component_name'] = $args['component_name'];
			$where_clauses['format'][] = '%s';
		}

		// component_action
		if ( ! empty( $args['component_action'] ) ) {
			$where_clauses['data']['component_action'] = $args['component_action'];
			$where_clauses['format'][] = '%s';
		}

		// item_id
		if ( ! empty( $args['item_id'] ) ) {
			$where_clauses['data']['item_id'] = absint( $args['item_id'] );
			$where_clauses['format'][] = '%d';
		}

		// secondary_item_id
		if ( ! empty( $args['secondary_item_id'] ) ) {
			$where_clauses['data']['secondary_item_id'] = absint( $args['secondary_item_id'] );
			$where_clauses['format'][] = '%d';
		}

		// invite_sent
		// @TODO how to handle. As "draft", "sent" & "all"?
		if ( isset( $args['invite_sent'] ) ) {
			$where_clauses['data']['invite_sent'] = ! empty( $args['invite_sent'] ) ? 1 : 0;
			$where_clauses['format'][] = '%d';
		}

		return $where_clauses;
	}

	/** Public Static Methods *********************************************/

	/**
	 * @TODO: use?
	 * Check that a specific invitation is for a specific user.
	 *
	 * @since BuddyPress (2.3.0)
	 *
	 * @param int $user_id ID of the user being checked.
	 * @param int $invitation_id ID of the invitation being checked.
	 * @return bool True if the invitation belongs to the user, otherwise
	 *         false.
	 */
	public static function check_access( $user_id, $invitation_id ) {
		global $wpdb;

		$bp = buddypress();

		return $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$bp->core->table_name_invitations} WHERE id = %d AND user_id = %d", $invitation_id, $user_id ) );
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
	public static function get( $args = array() ) {
		global $wpdb;

		// Parse the arguments
		$r  = wp_parse_args( $args, array(
			'id'                => false,
			'user_id'           => false,
			'inviter_id'        => false,
			'invitee_email'     => false,
			'component_name'    => bp_invitations_get_registered_components(),
			'component_action'  => false,
			'item_id'           => false,
			'secondary_item_id' => false,
			'invite_sent'       => 'all',
			'search_terms'      => '',
			'order_by'          => false,
			'sort_order'        => false,
			'page'              => false,
			'per_page'          => false,
		) );

		// SELECT
		$select_sql = "SELECT *";

		// FROM
		$from_sql   = "FROM " . buddypress()->invitations->table_name;

		// WHERE
		$where_sql  = self::get_where_sql( array(
			'id'                => $r['id'],
			'user_id'           => $r['user_id'],
			'inviter_id'		=> $r['inviter_id'],
			'invitee_email'     => $r['invitee_email'],
			'component_name'    => $r['component_name'],
			'component_action'  => $r['component_action'],
			'item_id'           => $r['item_id'],
			'secondary_item_id' => $r['secondary_item_id'],
			'invite_sent'       => $r['invite_sent'],
			'search_terms'      => $r['search_terms'],
		) );

		// ORDER BY
		$order_sql  = self::get_order_by_sql( array(
			'order_by'   => $r['order_by'],
			'sort_order' => $r['sort_order']
		) );

		// LIMIT %d, %d
		$pag_sql    = self::get_paged_sql( array(
			'page'     => $r['page'],
			'per_page' => $r['per_page'],
		) );

		$sql = "{$select_sql} {$from_sql} {$where_sql} {$order_sql} {$pag_sql}";

		return $wpdb->get_results( $sql );
	}

	/**
	 * Get a count of total invitations matching a set of arguments.
	 *
	 * @since BuddyPress (2.3.0)
	 *
	 * @see BP_Invitations_Invitation::get() for a description of
	 *      arguments.
	 *
	 * @param array $args See {@link BP_Invitations_Invitation::get()}.
	 * @return int Count of located items.
	 */
	public static function get_total_count( $args ) {
		global $wpdb;

		/**
		 * Default component_name to active_components
		 *
		 * @see http://buddypress.trac.wordpress.org/ticket/5300
		 */
		$args = wp_parse_args( $args, array(
			'component_name' => bp_invitations_get_registered_components()
		) );

		// Load BuddyPress
		$bp = buddypress();

		// Build the query
		$select_sql = "SELECT COUNT(*)";
		$from_sql   = "FROM {$bp->invitations->table_name}";
		$where_sql  = self::get_where_sql( $args );
		$sql        = "{$select_sql} {$from_sql} {$where_sql}";

		// Return the queried results
		return $wpdb->get_var( $sql );
	}

	/**
	 * Update invitations.
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
	public static function update( $update_args = array(), $where_args = array() ) {
		$update = self::get_query_clauses( $update_args );
		$where  = self::get_query_clauses( $where_args  );

		// make sure we delete the invitation cache for the user on update
		// @TODO: This won't fire if we're deleting a single invite by id.
		if ( ! empty( $where_args['user_id'] ) ) {
			wp_cache_delete( 'all_to_user_' . $where_args['user_id'], 'bp_invitations' );
		}

		if ( ! empty( $where_args['inviter_id'] ) ) {
			wp_cache_delete( 'all_to_user_' . $where_args['inviter_id'], 'bp_invitations' );
		}

		return self::_update( $update['data'], $where['data'], $update['format'], $where['format'] );
	}

	/**
	 * Delete invitations.
	 *
	 * @since BuddyPress (2.3.0)
	 *
	 * @see BP_Invitations_Invitation::get() for a description of
	 *      accepted update/where arguments.
	 *
	 * @param array $args Associative array of columns/values, to determine
	 *        which rows should be deleted.  Of the format
	 *            array( 'item_id' => 7, 'component_action' => 'members', )
	 * @return int|bool Number of rows deleted on success, false on failure.
	 */
	public static function delete( $args = array() ) {
		$where = self::get_query_clauses( $args );

		do_action( 'bp_invitation_before_delete', $args );

		return self::_delete( $where['data'], $where['format'] );
	}

	/** Convenience methods ***********************************************/

	/**
	 * Delete a single invitation by ID.
	 *
	 * @since BuddyPress (2.3.0)
	 *
	 * @see BP_Invitations_Invitation::delete() for explanation of
	 *      return value.
	 *
	 * @param int $id ID of the invitation item to be deleted.
	 * @return bool True on success, false on failure.
	 */
	public static function delete_by_id( $id ) {
		return self::delete( array(
			'id' => $id,
		) );
	}

	/**
	 * Fetch "sent" incoming invitations to a specific user.
	 *
	 * @since BuddyPress (2.3.0)
	 *
	 * @param int $user_id ID of the user whose incoming invitations are being
	 *        fetched.
	 * @return array Associative array of outstanding invitations.
	 */
	public static function get_sent_to_user( $user_id = 0 ) {
		return self::get( array(
			'user_id' => $user_id,
			'invite_sent'  => 'sent',
		) );
	}

	/**
	 * Fetch all outgoing invitations in the database from a specific user.
	 *
	 * @since BuddyPress (2.3.0)
	 *
	 * @param int $user_id ID of the user whose outgoing invitations are being
	 *        fetched.
	 * @param string $status Optional. Status of invitations to fetch.
	 *        'draft', or 'sent' for that subset. 'all' to get all.
	 * @return array Associative array of invitation items.
	 */
	public static function get_all_from_user( $inviter_id, $status = 'all' ) {
		return self::get( array(
			'inviter_id' 	=> $inviter_id,
			'invite_sent'  	=> $status,
		) );
	}

	/**
	 * Fetch "sent" outgoing invitations from a specific user.
	 *
	 * @since BuddyPress (2.3.0)
	 *
	 * @param int $inviter_id ID of the user whose outgoing invitations are 
	 * 		  being fetched.
	 * @return array Associative array of outstanding invitations.
	 */
	public static function get_sent_from_user( $inviter_id = 0 ) {
		return self::get( array(
			'inviter_id' 	=> $inviter_id,
			'invite_sent'  	=> 'sent',
		) );
	}

	/**
	 * Fetch all the read invitations in the database for a specific user.
	 *
	 * @since BuddyPress (2.3.0)
	 *
	 * @param int $inviter_id ID of the user whose outgoing invitations are 
	 * 		  being fetched.
	 * @return array Associative array of unread invitation items.
	 */
	public static function get_draft_from_user( $inviter_id = 0 ) {
		return self::get( array(
			'inviter_id' 	=> $inviter_id,
			'invite_sent'  	=> 'draft',
		) );
	}

	/**
	 * Get incoming invitations for a user, in a pagination-friendly format.
	 *
	 * @since BuddyPress (2.3.0)
	 *
	 * @param array $args {
	 *     Array of arguments.
	 *     @type int $user_id ID of the user for whom the invitations are
	 *           being fetched. Default: logged-in user ID.
	 *     @type string $invite_sent Limit to draft, sent or all invitations. 
	 *			 'draft' returns only unsent invitations, 'sent' returns only 
	 *			 sent invitations, 'all' returns all. Default: 'sent'. 
	 *     @type int $page Number of the page to return. Default: 1.
	 *     @type int $per_page Number of results to display per page.
	 *           Default: 10.
	 *     @type string $search_terms Optional. A term to search against in
	 *           the 'component_name' and 'component_action' columns.
	 * }
	 * @return array {
	 *     @type array $invitations Array of invitation results.
	 *     @type int $total Count of all located invitations matching
	 *           the query params.
	 * }
	 */
	public static function get_incoming_invitations_for_user( $args = array() ) {
		$r = wp_parse_args( $args, array(
			'user_id'      	=> bp_loggedin_user_id(),
			'invite_sent'	=> 'sent',
			'page'         	=> 1,
			'per_page'     	=> 10,
			'search_terms' 	=> '',
		) );

		$invitations = self::get( $r );

		// Bail if no invitations
		if ( empty( $invitations ) ) {
			return false;
		}

		$total_count = self::get_total_count( $r );

		return array( 'invitations' => &$invitations, 'total' => $total_count );
	}

	/**
	 * Get ougoing invitations for a user, in a pagination-friendly format.
	 *
	 * @since BuddyPress (2.3.0)
	 *
	 * @param array $args {
	 *     Array of arguments.
	 *     @type int $inviter_id ID of the user for whom the ougoing invitations
	 *			 are being fetched. Default: logged-in user ID.
	 *     @type string $invite_sent Limit to draft, sent or all invitations. 
	 *			 'draft' returns only unsent invitations, 'sent' returns only 
	 *			 sent invitations, 'all' returns all. Default: 'all'. 
	 *     @type int $page Number of the page to return. Default: 1.
	 *     @type int $per_page Number of results to display per page.
	 *           Default: 10.
	 *     @type string $search_terms Optional. A term to search against in
	 *           the 'component_name' and 'component_action' columns.
	 * }
	 * @return array {
	 *     @type array $invitations Array of invitation results.
	 *     @type int $total Count of all located invitations matching
	 *           the query params.
	 * }
	 */
	public static function get_outgoing_invitations_for_user( $args = array() ) {
		$r = wp_parse_args( $args, array(
			'user_id'      	=> bp_loggedin_user_id(),
			'invite_sent'	=> 'all',
			'page'         	=> 1,
			'per_page'     	=> 10,
			'search_terms' 	=> '',
		) );

		$invitations = self::get( $r );

		// Bail if no invitations
		if ( empty( $invitations ) ) {
			return false;
		}

		$total_count = self::get_total_count( $r );

		return array( 'invitations' => &$invitations, 'total' => $total_count );
	}

	/** Sent status ***********************************************************/

	/**
	 * Mark specific invitations as sent by invitation ID.
	 *
	 * @since BuddyPress (2.3.0)
	 *
	 * @param int $id The ID of the invitation to mark as sent.
	 */
	public static function mark_as_sent( $id = 0 ) {

		if ( ! $id ) {
			return false;
		}

		// Values to be updated
		$update_args = array(
			'invite_sent' => 1,
		);

		// WHERE clauses
		$where_args = array(
			'id' => $id,
		);

		return self::update( $update_args, $where_args );
	}

	/**
	 * Mark invitations as sent that are found by user_id, inviter_id, item id, and optional
	 * secondary item id, and component name and action.
	 *
	 * @since BuddyPress (2.3.0)
	 *
 	 * @param int $user_id ID of user being invited.
	 * @param int $inviter_id ID of user who created the invitation.
	 * @param string $component_name The component that the invitations
	 *        are associated with.
	 * @param string $component_action The action that the invitations
	 *        are associated with.
 	 * @param int $item_id The ID of the item associated with the
	 *        invitations.
	 * @param string $secondary_item_id Optional. ID of the secondary
	 *        associated item.
	 */
	public static function mark_sent_by_data( $user_id, $inviter_id, $component_name = '', $component_action = '', $item_id = 0, $secondary_item_id = 0 ) {

		// Values to be updated
		$update_args = array(
			'invite_sent' => 1,
		);

		// WHERE clauses
		$where_args = array(
			'item_id' => $item_id,
		);

		if ( ! empty( $component_name ) ) {
			$where_args['component_name'] = $component_name;
		}

		if ( ! empty( $component_action ) ) {
			$where_args['component_action'] = $component_action;
		}

		if ( ! empty( $secondary_item_id ) ) {
			$where_args['secondary_item_id'] = $secondary_item_id;
		}

		return self::update( $update_args, $where_args );
	}
}
