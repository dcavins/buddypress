<?php
/**
 * @group groups
 * @group BP_Groups_Member
 */
class BP_Tests_BP_Groups_Member_TestCases extends BP_UnitTestCase {
	public function setUp() {
		parent::setUp();
	}

	public function tearDown() {
		parent::tearDown();
	}

	public static function invite_user_to_group( $user_id, $group_id, $inviter_id, $draft = false ) {
		$invite                = new BP_Groups_Member;
		$invite->group_id      = $group_id;
		$invite->user_id       = $user_id;
		$invite->date_modified = bp_core_current_time();
		$invite->inviter_id    = $inviter_id;
		$invite->is_confirmed  = 0;
		$invite->invite_sent   = $draft ? 0 : 1;

		$invite->save();
		return $invite->id;
	}

	public static function create_group_membership_request( $user_id, $group_id ) {
		// Pending membership requests have inviter_id = 0, is_confirmed = 0
		$request                = new BP_Groups_Member;
		$request->group_id      = $group_id;
		$request->user_id       = $user_id;
		$request->date_modified = bp_core_current_time();
		$request->inviter_id    = 0;
		$request->is_confirmed  = 0;
		$request->is_admin  = 0;

		$request->save();
		return $request->id;
	}

	public function test_get_recently_joined_with_filter() {
		$g1 = $this->factory->group->create( array(
			'name' => 'Tab',
		) );
		$g2 = $this->factory->group->create( array(
			'name' => 'Diet Rite',
		) );

		$u = $this->factory->user->create();
		self::add_user_to_group( $u, $g1 );
		self::add_user_to_group( $u, $g2 );

		$groups = BP_Groups_Member::get_recently_joined( $u, false, false, 'Rite' );

		$ids = wp_list_pluck( $groups['groups'], 'id' );
		$this->assertEquals( $ids, array( $g2 ) );
	}

	public function test_get_is_admin_of_with_filter() {
		$g1 = $this->factory->group->create( array(
			'name' => 'RC Cola',
		) );
		$g2 = $this->factory->group->create( array(
			'name' => 'Pepsi',
		) );

		$u = $this->factory->user->create();
		self::add_user_to_group( $u, $g1 );
		self::add_user_to_group( $u, $g2 );

		$m1 = new BP_Groups_Member( $u, $g1 );
		$m1->promote( 'admin' );
		$m2 = new BP_Groups_Member( $u, $g2 );
		$m2->promote( 'admin' );

		$groups = BP_Groups_Member::get_is_admin_of( $u, false, false, 'eps' );

		$ids = wp_list_pluck( $groups['groups'], 'id' );
		$this->assertEquals( $ids, array( $g2 ) );
	}

	public function test_get_is_mod_of_with_filter() {
		$g1 = $this->factory->group->create( array(
			'name' => 'RC Cola',
		) );
		$g2 = $this->factory->group->create( array(
			'name' => 'Pepsi',
		) );

		$u = $this->factory->user->create();
		self::add_user_to_group( $u, $g1 );
		self::add_user_to_group( $u, $g2 );

		$m1 = new BP_Groups_Member( $u, $g1 );
		$m1->promote( 'mod' );
		$m2 = new BP_Groups_Member( $u, $g2 );
		$m2->promote( 'mod' );

		$groups = BP_Groups_Member::get_is_mod_of( $u, false, false, 'eps' );

		$ids = wp_list_pluck( $groups['groups'], 'id' );
		$this->assertEquals( $ids, array( $g2 ) );
	}

	public function test_get_invites_with_exclude() {
		$g1 = $this->factory->group->create( array(
			'name' => 'RC Cola',
		) );
		$g2 = $this->factory->group->create( array(
			'name' => 'Pepsi',
		) );

		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();
		self::add_user_to_group( $u1, $g1 );
		self::add_user_to_group( $u1, $g2 );
		self::invite_user_to_group( $u2, $g1, $u1 );
		self::invite_user_to_group( $u2, $g2, $u1 );

		$groups = BP_Groups_Member::get_invites( $u2, false, false, array( 'awesome', $g1 ) );

		$ids = wp_list_pluck( $groups['groups'], 'id' );
		$this->assertEquals( $ids, array( $g2 ) );
	}

	/**
	 * @expectedDeprecated BP_Groups_Member::get_all_for_group
	 */
	public function test_get_all_for_group_with_exclude() {
		$g1 = $this->factory->group->create();

		$u1 = $this->create_user();
		$u2 = $this->create_user();
		self::add_user_to_group( $u1, $g1 );
		self::add_user_to_group( $u2, $g1 );

		$members = BP_Groups_Member::get_all_for_group( $g1, false, false, true, true, array( $u1 ) );

		$mm = (array) $members['members'];
		$ids = wp_list_pluck( $mm, 'user_id' );
		$this->assertEquals( array( $u2 ), $ids );
	}

	public function test_groups_send_invite_by_invitee() {
		$g1 = $this->factory->group->create();

		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();
		$u3 = $this->factory->user->create();
		$u4 = $this->factory->user->create();
 		self::add_user_to_group( $u1, $g1 );

		// Test expected functionality
		self::invite_user_to_group( $u2, $g1, $u1, $draft = true );
		$this->assertTrue( is_null( groups_check_user_has_invite( $u2, $g1, $type = 'sent' ) ) );

		$success = groups_send_invite_by_invitee( $g1, $u2, $u1 );

		$this->assertTrue( $success );
		$this->assertTrue( is_numeric( groups_check_user_has_invite( $u2, $g1, $type = 'sent' ) ) );
		unset( $success );

		// Test when user doesn't have invitation.
		$this->assertTrue( is_null( groups_check_user_has_invite( $u3, $g1, $type = 'sent' ) ) );

		$success = groups_send_invite_by_invitee( $g1, $u3, $u1 );

		$this->assertFalse( $success );
		unset( $success );

		// Test when user has submitted a request for membership, but has not been invited
		self::create_group_membership_request( $u3, $g1 );
		$this->assertTrue( is_numeric( groups_check_for_membership_request( $u3, $g1 ) ) );
		$this->assertTrue( is_null( groups_check_user_has_invite( $u3, $g1, $type = 'all' ) ) );

		$success = groups_send_invite_by_invitee( $g1, $u3, $u1 );

		$this->assertFalse( $success );
		unset( $success );

		// Test when user already belongs to the group
		self::add_user_to_group( $u4, $g1 );

		$success = groups_send_invite_by_invitee( $g1, $u4, $u1 );

		$this->assertFalse( $success );
		unset( $success );

		// Test when user/group isn't specified
		$success = groups_send_invite_by_invitee( null, $u2, $u1 );

		$this->assertFalse( $success );
		unset( $success );

		$success = groups_send_invite_by_invitee( $g1, null, $u1 );

		$this->assertFalse( $success );
		unset( $success );

		// Test when user/group doesn't exist
		// Bad group ID
		$success = groups_send_invite_by_invitee( 457819810, $u2, $u1 );

		$this->assertFalse( $success );
		unset( $success );

		// Bad invitee ID
		$success = groups_send_invite_by_invitee( $g1, 47161302, $u1 );

		$this->assertFalse( $success );
		unset( $success );

		// Bad inviter ID
		$success = groups_send_invite_by_invitee( $g1, $u2, 472816989 );

		$this->assertFalse( $success );
		unset( $success );
	}

	public function test_groups_send_invites() {
		$g1 = $this->factory->group->create();
		$g1_obj = groups_get_group( array( 'group_id' => $g1 ) );

		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();
		$u3 = $this->factory->user->create();
		$u4 = $this->factory->user->create();
		$u5 = $this->factory->user->create();

		$old_current_user = get_current_user_id();

 		self::add_user_to_group( $u1, $g1 );

		// Test expected functionality
		// Create some draft invitations that we can send
		self::invite_user_to_group( $u2, $g1, $u1, $draft = true );
		self::invite_user_to_group( $u3, $g1, $u1, $draft = true );

		$success = groups_send_invites( $u1, $g1 );

		$this->assertTrue( $success );
		unset( $success );

		// User has no outstanding invites to send.
 		self::add_user_to_group( $u4, $g1 );
		$success = groups_send_invites( $u4, $g1 );
		
		$this->assertTrue( $success );
		unset( $success );

		// User doesn't belong to group.
		$success = groups_send_invites( $u5, $g1 );
		
		$this->assertFalse( $success );
		unset( $success );

		// Test when user/group isn't specified
		// User isn't specified, but current user does have outstanding invites.
		$this->set_current_user( $u1 );
		
		$success = groups_send_invites( null, $g1 );

		$this->assertTrue( $success );
		unset( $success );
		$this->set_current_user( $old_current_user );

		// Group isn't specified, we're not in a group context
		$success = groups_send_invites( $u1, null );

		$this->assertFalse( $success );
		unset( $success );

		// Group isn't specified, we are in a group context
		$this->go_to( bp_get_group_permalink( $g1_obj ) );

		$success = groups_send_invites( $u1, null );

		$this->assertTrue( $success );
		unset( $success );

		// Test when user/group doesn't exist
		// Bad user ID
		$success = groups_send_invites( 569845135, $g1 );

		$this->assertFalse( $success );
		unset( $success );

		// Bad group ID
		$success = groups_send_invites( $u1, 586234125 );

		$this->assertFalse( $success );
		unset( $success );
	}

	public function test_bp_groups_user_can_send_invites() {
		$u_members = $this->factory->user->create();
		$u_mods = $this->factory->user->create();
		$u_admins = $this->factory->user->create();
		$u_siteadmin = $this->factory->user->create();
		$user_siteadmin = new WP_User( $u_siteadmin );
		$user_siteadmin->add_role( 'administrator' );

		$g = $this->factory->group->create();

		$now = time();
		$old_current_user = get_current_user_id();

		// Create member-level user
		$this->add_user_to_group( $u_members, $g, array(
			'date_modified' => date( 'Y-m-d H:i:s', $now - 60 ),
		) );
		// Create mod-level user
		$this->add_user_to_group( $u_mods, $g, array(
			'date_modified' => date( 'Y-m-d H:i:s', $now - 60 ),
		) );
		$m_mod = new BP_Groups_Member( $u_mods, $g );
		$m_mod->promote( 'mod' );
		// Create admin-level user
		$this->add_user_to_group( $u_admins, $g, array(
			'date_modified' => date( 'Y-m-d H:i:s', $now - 60 ),
		) );
		$m_admin = new BP_Groups_Member( $u_admins, $g );
		$m_admin->promote( 'admin' );

		// Test with no status
		// In bp_group_get_invite_status(), no status falls back to "members"
		$this->assertTrue( '' == groups_get_groupmeta( $g, 'invite_status' ) );
		$this->assertTrue( bp_groups_user_can_send_invites( $g, $u_members ) );
		$this->assertTrue( bp_groups_user_can_send_invites( $g, $u_mods ) );
		$this->assertTrue( bp_groups_user_can_send_invites( $g, $u_admins ) );
		$this->assertTrue( bp_groups_user_can_send_invites( $g, $u_siteadmin ) );

		// Test with members status
		groups_update_groupmeta( $g, 'invite_status', 'members' );
		$this->assertTrue( bp_groups_user_can_send_invites( $g, $u_members ) );
		$this->assertTrue( bp_groups_user_can_send_invites( $g, $u_mods ) );
		$this->assertTrue( bp_groups_user_can_send_invites( $g, $u_admins ) );
		$this->assertTrue( bp_groups_user_can_send_invites( $g, $u_siteadmin ) );
 		// Falling back to current user
		$this->set_current_user( $u_members );
 		$this->assertTrue( bp_groups_user_can_send_invites( $g, null ) );

		// Test with mod status
		groups_update_groupmeta( $g, 'invite_status', 'mods' );
		$this->assertFalse( bp_groups_user_can_send_invites( $g, $u_members ) );
		$this->assertTrue( bp_groups_user_can_send_invites( $g, $u_mods ) );
		$this->assertTrue( bp_groups_user_can_send_invites( $g, $u_admins ) );
		$this->assertTrue( bp_groups_user_can_send_invites( $g, $u_siteadmin ) );
 		// Falling back to current user
		$this->set_current_user( $u_members );
 		$this->assertFalse( bp_groups_user_can_send_invites( $g, null ) );
		$this->set_current_user( $u_mods );
 		$this->assertTrue( bp_groups_user_can_send_invites( $g, null ) );		

		// Test with admin status
		groups_update_groupmeta( $g, 'invite_status', 'admins' );
		$this->assertFalse( bp_groups_user_can_send_invites( $g, $u_members ) );
		$this->assertFalse( bp_groups_user_can_send_invites( $g, $u_mods ) );
		$this->assertTrue( bp_groups_user_can_send_invites( $g, $u_admins ) );
		$this->assertTrue( bp_groups_user_can_send_invites( $g, $u_siteadmin ) );
 		// Falling back to current user
		$this->set_current_user( $u_mods );
 		$this->assertFalse( bp_groups_user_can_send_invites( $g, null ) );
		$this->set_current_user( $u_admins );
 		$this->assertTrue( bp_groups_user_can_send_invites( $g, null ) );

		// Bad or null parameters
 		$this->assertFalse( bp_groups_user_can_send_invites( 59876454257, $u_members ) );
 		$this->assertFalse( bp_groups_user_can_send_invites( $g, 958647515 ) );
		// Not in group context
 		$this->assertFalse( bp_groups_user_can_send_invites( null, $u_members ) ); 		
 		// In group context
		$g_obj = groups_get_group( array( 'group_id' => $g ) );
		$this->go_to( bp_get_group_permalink( $g_obj ) );
		groups_update_groupmeta( $g, 'invite_status', 'members' );

 		$this->assertTrue( bp_groups_user_can_send_invites( null, $u_members ) );

		$this->set_current_user( $old_current_user );

	}
}

