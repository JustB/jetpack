<?php
require dirname( __FILE__ ) . '/../../../../modules/likes.php';

/**
 * @property Jetpack_Likes likes
 */
class WP_Test_Jetpack_Likes extends WP_UnitTestCase {

	public function setUp() {
		parent::setUp();

		delete_option( 'disabled_likes' );

		$this->likes = Jetpack_Likes::init();
	}

	public function test_is_enabled_sitewide_is_true_by_default() {
		$this->assertTrue( $this->likes->is_enabled_sitewide() );
	}

	public function test_is_enabled_sitewide_is_false_when_likes_are_disabled() {
		update_option( 'disabled_likes', 1 );
		$this->assertFalse( $this->likes->is_enabled_sitewide() );
	}

	public function test_with_no_arguments_post_shouldnt_be_likeable() {
		$this->assertFalse( $this->likes->is_post_likeable() );
	}

	public function test_with_default_values_post_should_be_likeable() {
		$post_id = $this->factory->post->create( array() );

		$this->assertTrue( $this->likes->is_post_likeable( $post_id ) );
	}

	public function test_when_disabled_sitewide_and_enabled_on_post_post_should_be_likeable() {
		$post_id = $this->factory->post->create( array() );

		update_option( 'disabled_likes', 1 );
		add_post_meta( $post_id, 'switch_like_status', 1 );

		$this->assertTrue( $this->likes->is_post_likeable( $post_id ) );
	}

	public function test_when_disabled_sitewide_and_disabled_on_post_post_shouldnt_be_likeable() {
		$post_id = $this->factory->post->create( array() );

		update_option( 'disabled_likes', 1 );

		$this->assertFalse( $this->likes->is_post_likeable( $post_id ) );
	}

	public function test_when_enabled_sitewide_and_disabled_on_post_post_should_be_likeable() {
		$post_id = $this->factory->post->create( array() );

		update_option( 'disabled_likes', 0 );

		$this->assertTrue( $this->likes->is_post_likeable( $post_id ) );
	}

	public function test_when_enabled_sitewide_and_enabled_on_post_post_should_be_likeable() {
		$post_id = $this->factory->post->create( array() );

		update_option( 'disabled_likes', 0 );
		add_post_meta( $post_id, 'switch_like_status', 1 );


		$this->assertTrue( $this->likes->is_post_likeable( $post_id ) );
	}

}
