<?php

/**
 * BuddyPress Invitations Template Functions
 *
 * @package BuddyPress
 * @subpackage InvitationsTemplate
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/** Main Loop *****************************************************************/

/**
 * The main invitations template loop class.
 *
 * Responsible for loading a group of invitations into a loop for display.
 *
 * @since BuddyPress (2.3.0)
 */
class BP_Invitations_Template {

	/**
	 * The loop iterator.
	 *
	 * @since BuddyPress (2.3.0)
	 * @access public
	 * @var int
	 */
	public $current_invitation = -1;

	/**
	 * The number of invitations returned by the paged query.
	 *
	 * @since BuddyPress (2.3.0)
	 * @access public
	 * @var int
	 */
	public $current_invitation_count;

	/**
	 * Total number of invitations matching the query.
	 *
	 * @since BuddyPress (2.3.0)
	 * @access public
	 * @var int
	 */
	public $total_invitation_count;

	/**
	 * Array of invitations located by the query.
	 *
	 * @since BuddyPress (2.3.0)
	 * @access public
	 * @var array
	 */
	public $invitations;

	/**
	 * The invitation object currently being iterated on.
	 *
	 * @since BuddyPress (2.3.0)
	 * @access public
	 * @var object
	 */
	public $invitation;

	/**
	 * A flag for whether the loop is currently being iterated.
	 *
	 * @since BuddyPress (2.3.0)
	 * @access public
	 * @var bool
	 */
	public $in_the_loop;

	/**
	 * The ID of the user to whom the displayed invitations belong.
	 *
	 * @since BuddyPress (2.3.0)
	 * @access public
	 * @var int
	 */
	public $user_id;

	/**
	 * The page number being requested.
	 *
	 * @since BuddyPress (2.3.0)
	 * @access public
	 * @var int
	 */
	public $pag_page;

	/**
	 * The number of items to display per page of results.
	 *
	 * @since BuddyPress (2.3.0)
	 * @access public
	 * @var int
	 */
	public $pag_num;

	/**
	 * An HTML string containing pagination links.
	 *
	 * @since BuddyPress (2.3.0)
	 * @access public
	 * @var string
	 */
	public $pag_links;

	/**
	 * A string to match against.
	 *
	 * @since BuddyPress (2.3.0)
	 * @access public
	 * @var string
	 */
	public $search_terms;

	/**
	 * A database column to order the results by.
	 *
	 * @since BuddyPress (2.3.0)
	 * @access public
	 * @var string
	 */
	public $order_by;

	/**
	 * The direction to sort the results (ASC or DESC)
	 *
	 * @since BuddyPress (2.3.0)
	 * @access public
	 * @var string
	 */
	public $sort_order;

	/**
	 * Constructor method.
	 *
	 * @see bp_has_invitations() For information on the array format.
	 *
	 * @since BuddyPress (2.3.0)
	 *
	 * @param array $args {
	 *     An array of arguments. See {@link bp_has_invitations()}
	 *     for more details.
	 * }
	 */
	public function __construct( $args = array() ) {

		// Parse arguments
		$r = wp_parse_args( $args, array(
			'id'                => false,
			'user_id'           => 0,
			'secondary_item_id' => false,
			'component_name'    => bp_invitations_get_registered_components(),
			'component_action'  => false,
			'is_new'            => true,
			'search_terms'      => '',
			'order_by'          => 'date_notified',
			'sort_order'        => 'DESC',
			'page'              => 1,
			'per_page'          => 25,
			'max'               => null,
			'page_arg'          => 'npage',
		) );

		// Overrides

		// Set which pagination page
		if ( isset( $_GET[ $r['page_arg'] ] ) ) {
			$r['page'] = intval( $_GET[ $r['page_arg'] ] );
		}

		// Set the number to show per page
		if ( isset( $_GET['num'] ) ) {
			$r['per_page'] = intval( $_GET['num'] );
		} else {
			$r['per_page'] = intval( $r['per_page'] );
		}

		// Sort order direction
		$orders = array( 'ASC', 'DESC' );
		if ( ! empty( $_GET['sort_order'] ) && in_array( $_GET['sort_order'], $orders ) ) {
			$r['sort_order'] = $_GET['sort_order'];
		} else {
			$r['sort_order'] = in_array( $r['sort_order'], $orders ) ? $r['sort_order'] : 'DESC';
		}

		// Setup variables
		$this->pag_page     = $r['page'];
		$this->pag_num      = $r['per_page'];
		$this->user_id      = $r['user_id'];
		$this->is_new       = $r['is_new'];
		$this->search_terms = $r['search_terms'];
		$this->page_arg     = $r['page_arg'];
		$this->order_by     = $r['order_by'];
		$this->sort_order   = $r['sort_order'];

		// Setup the invitations to loop through
		$this->invitations            = BP_Invitations_Notification::get( $r );
		$this->total_invitation_count = BP_Invitations_Notification::get_total_count( $r );

		if ( empty( $this->invitations ) ) {
			$this->invitation_count       = 0;
			$this->total_invitation_count = 0;

		} else {
			if ( ! empty( $r['max'] ) ) {
				if ( $r['max'] >= count( $this->invitations ) ) {
					$this->invitation_count = count( $this->invitations );
				} else {
					$this->invitation_count = (int) $r['max'];
				}
			} else {
				$this->invitation_count = count( $this->invitations );
			}
		}

		if ( (int) $this->total_invitation_count && (int) $this->pag_num ) {
			$this->pag_links = paginate_links( array(
				'base'      => add_query_arg( $this->page_arg, '%#%' ),
				'format'    => '',
				'total'     => ceil( (int) $this->total_invitation_count / (int) $this->pag_num ),
				'current'   => $this->pag_page,
				'prev_text' => _x( '&larr;', 'Invitations pagination previous text', 'buddypress' ),
				'next_text' => _x( '&rarr;', 'Invitations pagination next text',     'buddypress' ),
				'mid_size'  => 1,
			) );

			// Remove first page from pagination
			$this->pag_links = str_replace( '?'      . $r['page_arg'] . '=1', '', $this->pag_links );
			$this->pag_links = str_replace( '&#038;' . $r['page_arg'] . '=1', '', $this->pag_links );
		}
	}

	/**
	 * Whether there are invitations available in the loop.
	 *
	 * @since BuddyPress (2.3.0)
	 *
	 * @see bp_has_invitations()
	 *
	 * @return bool True if there are items in the loop, otherwise false.
	 */
	public function has_invitations() {
		if ( $this->invitation_count ) {
			return true;
		}

		return false;
	}

	/**
	 * Set up the next invitation and iterate index.
	 *
	 * @since BuddyPress (2.3.0)
	 *
	 * @return object The next invitation to iterate over.
	 */
	public function next_invitation() {

		$this->current_invitation++;

		$this->invitation = $this->invitations[ $this->current_invitation ];

		return $this->invitation;
	}

	/**
	 * Rewind the blogs and reset blog index.
	 *
	 * @since BuddyPress (2.3.0)
	 */
	public function rewind_invitations() {

		$this->current_invitation = -1;

		if ( $this->invitation_count > 0 ) {
			$this->invitation = $this->invitations[0];
		}
	}

	/**
	 * Whether there are invitations left in the loop to iterate over.
	 *
	 * This method is used by {@link bp_invitations()} as part of the
	 * while loop that controls iteration inside the invitations loop, eg:
	 *     while ( bp_invitations() ) { ...
	 *
	 * @since BuddyPress (2.3.0)
	 *
	 * @see bp_invitations()
	 *
	 * @return bool True if there are more invitations to show,
	 *         otherwise false.
	 */
	public function invitations() {

		if ( $this->current_invitation + 1 < $this->invitation_count ) {
			return true;

		} elseif ( $this->current_invitation + 1 == $this->invitation_count ) {
			do_action( 'invitations_loop_end');

			$this->rewind_invitations();
		}

		$this->in_the_loop = false;
		return false;
	}

	/**
	 * Set up the current invitation inside the loop.
	 *
	 * Used by {@link bp_the_invitation()} to set up the current
	 * invitation data while looping, so that template tags used during
	 * that iteration make reference to the current invitation.
	 *
	 * @since BuddyPress (2.3.0)
	 *
	 * @see bp_the_invitation()
	 */
	public function the_invitation() {
		$this->in_the_loop  = true;
		$this->invitation = $this->next_invitation();

		// loop has just started
		if ( 0 === $this->current_invitation ) {
			do_action( 'invitations_loop_start' );
		}
	}
}

/** The Loop ******************************************************************/

/**
 * Initialize the invitations loop.
 *
 * Based on the $args passed, bp_has_invitations() populates
 * buddypress()->invitations->query_loop global, enabling the use of BP
 * templates and template functions to display a list of invitations.
 *
 * @since BuddyPress (2.3.0)
 *
 * @param array $args {
 *     Arguments for limiting the contents of the invitations loop. Can be
 *     passed as an associative array, or as a URL query string.
 *
 *     See {@link BP_Invitations_Notification::get()} for detailed
 *     information on the arguments.  In addition, also supports:
 *
 *     @type int $max Optional. Max items to display. Default: false.
 *     @type string $page_arg URL argument to use for pagination.
 *           Default: 'npage'.
 * }
 */
function bp_has_invitations( $args = '' ) {

	// Get the default is_new argument
	if ( bp_is_current_action( 'unread' ) ) {
		$is_new = 1;
	} elseif ( bp_is_current_action( 'read' ) ) {
		$is_new = 0;

	// not on a invitations page? default to fetch new invitations
	} else {
		$is_new = 1;
	}

	// Get the user ID
	if ( bp_displayed_user_id() ) {
		$user_id = bp_displayed_user_id();
	} else {
		$user_id = bp_loggedin_user_id();
	}

	// Parse the args
	$r = bp_parse_args( $args, array(
		'id'                => false,
		'user_id'           => $user_id,
		'secondary_item_id' => false,
		'component_name'    => bp_invitations_get_registered_components(),
		'component_action'  => false,
		'is_new'            => $is_new,
		'search_terms'      => isset( $_REQUEST['s'] ) ? stripslashes( $_REQUEST['s'] ) : '',
		'order_by'          => 'date_notified',
		'sort_order'        => 'DESC',
		'page'              => 1,
		'per_page'          => 25,

		// these are additional arguments that are not available in
		// BP_Invitations_Notification::get()
		'max'               => false,
		'page_arg'          => 'npage',
	), 'has_invitations' );

	// Get the invitations
	$query_loop = new BP_Invitations_Template( $r );

	// Setup the global query loop
	buddypress()->invitations->query_loop = $query_loop;

	return apply_filters( 'bp_has_invitations', $query_loop->has_invitations(), $query_loop );
}

/**
 * Get the invitations returned by the template loop.
 *
 * @since BuddyPress (2.3.0)
 *
 * @return array List of invitations.
 */
function bp_the_invitations() {
	return buddypress()->invitations->query_loop->invitations();
}

/**
 * Get the current invitation object in the loop.
 *
 * @since BuddyPress (2.3.0)
 *
 * @return object The current invitation within the loop.
 */
function bp_the_invitation() {
	return buddypress()->invitations->query_loop->the_invitation();
}

/** Loop Output ***************************************************************/

/**
 * Output the ID of the invitation currently being iterated on.
 *
 * @since BuddyPress (2.3.0)
 */
function bp_the_invitation_id() {
	echo bp_get_the_invitation_id();
}
	/**
	 * Return the ID of the invitation currently being iterated on.
	 *
	 * @since BuddyPress (2.3.0)
	 *
	 * @return int ID of the current invitation.
	 */
	function bp_get_the_invitation_id() {
		return apply_filters( 'bp_get_the_invitation_id', buddypress()->invitations->query_loop->invitation->id );
	}

/**
 * Output the associated item ID of the invitation currently being iterated on.
 *
 * @since BuddyPress (2.3.0)
 */
function bp_the_invitation_item_id() {
	echo bp_get_the_invitation_item_id();
}
	/**
	 * Return the associated item ID of the invitation currently being iterated on.
	 *
	 * @since BuddyPress (2.3.0)
	 *
	 * @return int ID of the item associated with the current invitation.
	 */
	function bp_get_the_invitation_item_id() {
		return apply_filters( 'bp_get_the_invitation_item_id', buddypress()->invitations->query_loop->invitation->item_id );
	}

/**
 * Output the secondary associated item ID of the invitation currently being iterated on.
 *
 * @since BuddyPress (2.3.0)
 */
function bp_the_invitation_secondary_item_id() {
	echo bp_get_the_invitation_secondary_item_id();
}
	/**
	 * Return the secondary associated item ID of the invitation currently being iterated on.
	 *
	 * @since BuddyPress (2.3.0)
	 *
	 * @return int ID of the secondary item associated with the current invitation.
	 */
	function bp_get_the_invitation_secondary_item_id() {
		return apply_filters( 'bp_get_the_invitation_secondary_item_id', buddypress()->invitations->query_loop->invitation->secondary_item_id );
	}

/**
 * Output the name of the component associated with the invitation currently being iterated on.
 *
 * @since BuddyPress (2.3.0)
 */
function bp_the_invitation_component_name() {
	echo bp_get_the_invitation_component_name();
}
	/**
	 * Return the name of the component associated with the invitation currently being iterated on.
	 *
	 * @since BuddyPress (2.3.0)
	 *
	 * @return int Name of the component associated with the current invitation.
	 */
	function bp_get_the_invitation_component_name() {
		return apply_filters( 'bp_get_the_invitation_component_name', buddypress()->invitations->query_loop->invitation->component_name );
	}

/**
 * Output the name of the action associated with the invitation currently being iterated on.
 *
 * @since BuddyPress (2.3.0)
 */
function bp_the_invitation_component_action() {
	echo bp_get_the_invitation_component_action();
}
	/**
	 * Return the name of the action associated with the invitation currently being iterated on.
	 *
	 * @since BuddyPress (2.3.0)
	 *
	 * @return int Name of the action associated with the current invitation.
	 */
	function bp_get_the_invitation_component_action() {
		return apply_filters( 'bp_get_the_invitation_component_action', buddypress()->invitations->query_loop->invitation->component_action );
	}

/**
 * Output the timestamp of the current invitation.
 *
 * @since BuddyPress (2.3.0)
 */
function bp_the_invitation_date_notified() {
	echo bp_get_the_invitation_date_notified();
}
	/**
	 * Return the timestamp of the current invitation.
	 *
	 * @since BuddyPress (2.3.0)
	 *
	 * @return string Timestamp of the current invitation.
	 */
	function bp_get_the_invitation_date_notified() {
		return apply_filters( 'bp_get_the_invitation_date_notified', buddypress()->invitations->query_loop->invitation->date_notified );
	}

/**
 * Output the timestamp of the current invitation.
 *
 * @since BuddyPress (2.3.0)
 */
function bp_the_invitation_time_since() {
	echo bp_get_the_invitation_time_since();
}
	/**
	 * Return the timestamp of the current invitation.
	 *
	 * @since BuddyPress (2.3.0)
	 *
	 * @return string Timestamp of the current invitation.
	 */
	function bp_get_the_invitation_time_since() {

		// Get the notified date
		$date_notified = bp_get_the_invitation_date_notified();

		// Notified date has legitimate data
		if ( '0000-00-00 00:00:00' !== $date_notified ) {
			$retval = bp_core_time_since( $date_notified );

		// Notified date is empty, so return a fun string
		} else {
			$retval = __( 'Date not found', 'buddypress' );
		}

		return apply_filters( 'bp_get_the_invitation_time_since', $retval );
	}

/**
 * Output full-text description for a specific invitation.
 *
 * @since BuddyPress (2.3.0)
 */
function bp_the_invitation_description() {
	echo bp_get_the_invitation_description();
}

	/**
	 * Get full-text description for a specific invitation.
	 *
	 * @since BuddyPress (2.3.0)
	 *
	 * @return string
	 */
	function bp_get_the_invitation_description() {

		// Setup local variables
		$description  = '';
		$bp           = buddypress();
		$invitation = $bp->invitations->query_loop->invitation;

		// Callback function exists
		if ( isset( $bp->{ $invitation->component_name }->invitation_callback ) && is_callable( $bp->{ $invitation->component_name }->invitation_callback ) ) {
			$description = call_user_func( $bp->{ $invitation->component_name }->invitation_callback, $invitation->component_action, $invitation->item_id, $invitation->secondary_item_id, 1 );

		// @deprecated format_invitation_function - 1.5
		} elseif ( isset( $bp->{ $invitation->component_name }->format_invitation_function ) && function_exists( $bp->{ $invitation->component_name }->format_invitation_function ) ) {
			$description = call_user_func( $bp->{ $invitation->component_name }->format_invitation_function, $invitation->component_action, $invitation->item_id, $invitation->secondary_item_id, 1 );

		// Allow non BuddyPress components to hook in
		} else {
			$description = apply_filters_ref_array( 'bp_invitations_get_invitations_for_user', array( $invitation->component_action, $invitation->item_id, $invitation->secondary_item_id, 1 ) );
		}

		// Filter and return
		return apply_filters( 'bp_get_the_invitation_description', $description );
	}

/**
 * Output the delete link for the current invitation.
 *
 * @since BuddyPress (2.3.0)
 *
 * @uses bp_get_the_invitation_delete_link()
 */
function bp_the_invitation_delete_link() {
	echo bp_get_the_invitation_delete_link();
}
	/**
	 * Return the delete link for the current invitation.
	 *
	 * @since BuddyPress (2.3.0)
	 */
	function bp_get_the_invitation_delete_link() {

		// Start the output buffer
		ob_start(); ?>

		<a href="<?php bp_the_invitation_delete_url(); ?>" class="delete secondary confirm"><?php _e( 'Delete', 'buddypress' ); ?></a>

		<?php $retval = ob_get_clean();

		return apply_filters( 'bp_get_the_invitation_delete_link', $retval );
	}

/**
 * Output the URL used for deleting a single invitation
 *
 * Since this function directly outputs a URL, it is escaped.
 *
 * @since BuddyPress (2.1.0)
 *
 * @uses esc_url()
 * @uses bp_get_the_invitation_delete_url()
 */
function bp_the_invitation_delete_url() {
	echo esc_url( bp_get_the_invitation_delete_url() );
}
	/**
	 * Return the URL used for deleting a single invitation
	 *
	 * @since BuddyPress (2.1.0)
	 *
	 * @return string
	 */
	function bp_get_the_invitation_delete_url() {

		// URL to add nonce to
		if ( bp_is_current_action( 'unread' ) ) {
			$link = bp_get_invitations_unread_permalink();
		} elseif ( bp_is_current_action( 'read' ) ) {
			$link = bp_get_invitations_read_permalink();
		}

		// Get the ID
		$id = bp_get_the_invitation_id();

		// Get the args to add to the URL
		$args = array(
			'action'          => 'delete',
			'invitation_id' => $id
		);

		// Add the args
		$url = add_query_arg( $args, $link );

		// Add the nonce
		$url = wp_nonce_url( $url, 'bp_invitation_delete_' . $id );

		// Filter and return
		return apply_filters( 'bp_get_the_invitation_delete_url', $url );
	}

/**
 * Output the action links for the current invitation.
 *
 * @since BuddyPress (2.3.0)
 */
function bp_the_invitation_action_links( $args = '' ) {
	echo bp_get_the_invitation_action_links( $args );
}
	/**
	 * Return the action links for the current invitation.
	 *
	 * @since BuddyPress (2.3.0)
	 *
	 * @param array $args {
	 *     @type string $before HTML before the links.
	 *     @type string $after HTML after the links.
	 *     @type string $sep HTML between the links.
	 *     @type array $links Array of links to implode by 'sep'.
	 * }
	 *
	 * @return string HTML links for actions to take on single invitations.
	 */
	function bp_get_the_invitation_action_links( $args = '' ) {

		// Parse
		$r = wp_parse_args( $args, array(
			'before' => '',
			'after'  => '',
			'sep'    => ' | ',
			'links'  => array(
				bp_get_the_invitation_mark_link(),
				bp_get_the_invitation_delete_link()
			)
		) );

		// Build the links
		$retval = $r['before'] . implode( $r['links'], $r['sep'] ) . $r['after'];

		return apply_filters( 'bp_get_the_invitation_action_links', $retval );
	}

/**
 * Output the pagination count for the current invitation loop.
 *
 * @since BuddyPress (2.3.0)
 */
function bp_invitations_pagination_count() {
	echo bp_get_invitations_pagination_count();
}
	/**
	 * Return the pagination count for the current invitation loop.
	 *
	 * @since BuddyPress (2.3.0)
	 *
	 * @return string HTML for the pagination count.
	 */
	function bp_get_invitations_pagination_count() {
		$query_loop = buddypress()->invitations->query_loop;
		$start_num  = intval( ( $query_loop->pag_page - 1 ) * $query_loop->pag_num ) + 1;
		$from_num   = bp_core_number_format( $start_num );
		$to_num     = bp_core_number_format( ( $start_num + ( $query_loop->pag_num - 1 ) > $query_loop->total_invitation_count ) ? $query_loop->total_invitation_count : $start_num + ( $query_loop->pag_num - 1 ) );
		$total      = bp_core_number_format( $query_loop->total_invitation_count );
		$pag        = sprintf( _n( 'Viewing 1 invitation', 'Viewing %1$s - %2$s of %3$s invitations', $total, 'buddypress' ), $from_num, $to_num, $total );

		return apply_filters( 'bp_invitations_pagination_count', $pag );
	}

/**
 * Output the pagination links for the current invitation loop.
 *
 * @since BuddyPress (2.3.0)
 */
function bp_invitations_pagination_links() {
	echo bp_get_invitations_pagination_links();
}
	/**
	 * Return the pagination links for the current invitation loop.
	 *
	 * @since BuddyPress (2.3.0)
	 *
	 * @return string HTML for the pagination links.
	 */
	function bp_get_invitations_pagination_links() {
		return apply_filters( 'bp_get_invitations_pagination_links', buddypress()->invitations->query_loop->pag_links );
	}

/** Form Helpers **************************************************************/

/**
 * Output the form for changing the sort order of invitations
 *
 * @since BuddyPress (2.3.0)
 */
function bp_invitations_sort_order_form() {

	// Setup local variables
	$orders   = array( 'DESC', 'ASC' );
	$selected = 'DESC';

	// Check for a custom sort_order
	if ( !empty( $_REQUEST['sort_order'] ) ) {
		if ( in_array( $_REQUEST['sort_order'], $orders ) ) {
			$selected = $_REQUEST['sort_order'];
		}
	} ?>

	<form action="" method="get" id="invitations-sort-order">
		<label for="invitations-friends"><?php esc_html_e( 'Order By:', 'buddypress' ); ?></label>

		<select id="invitations-sort-order-list" name="sort_order" onchange="this.form.submit();">
			<option value="DESC" <?php selected( $selected, 'DESC' ); ?>><?php _e( 'Newest First', 'buddypress' ); ?></option>
			<option value="ASC"  <?php selected( $selected, 'ASC'  ); ?>><?php _e( 'Oldest First', 'buddypress' ); ?></option>
		</select>

		<noscript>
			<input id="submit" type="submit" name="form-submit" class="submit" value="<?php esc_attr_e( 'Go', 'buddypress' ); ?>" />
		</noscript>
	</form>

<?php
}