<?php
namespace Tribe\Events\Pro\Supports\WPML\API;

use Helper\RecurringEvents;
use tad\FunctionMocker\FunctionMocker;
use Tribe__Events__Main as Main;
use Tribe__Events__Pro__Supports__WPML__API__Translations as Translations;
use Tribe__Events__Pro__Supports__WPML__WPML as WPML;

class TranslationsTest extends \Codeception\TestCase\WPTestCase {

	/**
	 * @var RecurringEvents
	 */
	protected $recurring_events;

	public function setUp() {
		// before
		parent::setUp();

		// your set up methods here
		FunctionMocker::setUp();
		$this->recurring_events = $this->getModule( '\Helper\RecurringEvents' );
	}

	public function tearDown() {
		// your tear down methods here
		FunctionMocker::tearDown();

		// then
		parent::tearDown();
	}

	/**
	 * @test
	 * it should be instantiatable
	 */
	public function it_should_be_instantiatable() {
		$sut = $this->make_instance();

		$this->assertInstanceOf( 'Tribe__Events__Pro__Supports__WPML__API__Translations', $sut );
	}

	/**
	 * @test
	 * it should get the parent language code from the globals if defined
	 *
	 * Will happen if creation happens while saving a post form the new post or edit screen.
	 */
	public function it_should_get_the_parent_language_code_from_the_globals_if_defined() {
		$_POST[ WPML::$post_language_post_global_key ] = 'it';
		$parent_post_id                                = $this->factory->post->create( [ 'post_type' => Main::POSTTYPE ] );
		$wpml_get_language_information                 = FunctionMocker::replace( 'wpml_get_language_information' );

		$sut           = $this->make_instance();
		$language_code = $sut->get_parent_language_code( $parent_post_id );

		$this->assertEquals( 'it', $language_code );
		$wpml_get_language_information->wasNotCalled();
	}

	/**
	 * @test
	 * it should get the parent language code from the db if not defined in the globals
	 *
	 * Will happen when creation happens  in the context of a cron job or an AJAx request handling.
	 */
	public function it_should_get_the_parent_language_code_from_the_db_if_not_defined_in_the_globals() {
		unset( $_POST[ WPML::$post_language_post_global_key ] );
		$parent_post_id                = $this->factory->post->create( [ 'post_type' => Main::POSTTYPE ] );
		$wpml_get_language_information = FunctionMocker::replace( 'wpml_get_language_information', [ 'language_code' => 'it' ] );

		$sut           = $this->make_instance();
		$language_code = $sut->get_parent_language_code( $parent_post_id );

		$this->assertEquals( 'it', $language_code );
		$wpml_get_language_information->wasCalledWithOnce( [ null, $parent_post_id ] );
	}

	/**
	 * @test
	 * it should return false if parent language code is not defined in global or db
	 */
	public function it_should_return_false_if_parent_language_code_is_not_defined_in_global_or_db() {
		unset( $_POST[ WPML::$post_language_post_global_key ] );
		$parent_post_id = $this->factory->post->create( [ 'post_type' => Main::POSTTYPE ] );
		FunctionMocker::replace( 'wpml_get_language_information', false );

		$sut           = $this->make_instance();
		$language_code = $sut->get_parent_language_code( $parent_post_id );

		$this->assertFalse( $language_code );
	}

	/**
	 * @test
	 * it should return false if trying to get master series event trid of non recurring event
	 */
	public function it_should_return_false_if_trying_to_get_master_series_event_trid_of_non_recurring_event() {
		$parent_event_id = $this->factory()->post->create( [ 'post_type' => Main::POSTTYPE ] );
		$child_event_id  = $this->factory()->post->create( [ 'post_type' => Main::POSTTYPE, 'post_parent' => $parent_event_id ] );

		$sut = $this->make_instance();

		$this->assertFalse( $sut->get_master_series_instance_trid( $child_event_id, $parent_event_id ) );
	}

	/**
	 * @test
	 * it should return false if child event has not a start date
	 */
	public function it_should_return_false_if_child_event_has_not_a_start_date() {
		$parent_event_id = $this->recurring_events->create_recurring_event();
		$child_event_id  = $this->recurring_events->last_series()->first_child();
		delete_post_meta( $child_event_id, '_EventStartDate' );

		$sut = $this->make_instance();

		$this->assertFalse( $sut->get_master_series_instance_trid( $child_event_id, $parent_event_id ) );
	}

	private function make_instance() {
		return new Translations();
	}
}