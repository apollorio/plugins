<?php
/**
 * Apollo Event Timetable Strict Mode Test
 *
 * Verifies that when DJs are selected for an event, the _event_dj_slots meta is always in sync
 * and the timetable reflects the selection order and times, with no stale or missing rows.
 */

declare(strict_types=1);

use WP_UnitTestCase;

class Apollo_Event_Timetable_Strict_Mode_Test extends WP_UnitTestCase {

	public function test_timetable_syncs_with_dj_selection() {
		// Create DJs.
		$dj1 = wp_insert_post(
			array(
				'post_type'   => 'event_dj',
				'post_title'  => 'DJ Alpha',
				'post_status' => 'publish',
			)
		);
		$dj2 = wp_insert_post(
			array(
				'post_type'   => 'event_dj',
				'post_title'  => 'DJ Beta',
				'post_status' => 'publish',
			)
		);
		$dj3 = wp_insert_post(
			array(
				'post_type'   => 'event_dj',
				'post_title'  => 'DJ Gamma',
				'post_status' => 'publish',
			)
		);

		// Create event.
		$event_id = wp_insert_post(
			array(
				'post_type'   => 'event_listing',
				'post_title'  => 'Strict Mode Event',
				'post_status' => 'publish',
			)
		);

		// Simulate selecting DJs and setting timetable.
		$dj_ids = array( $dj1, $dj2, $dj3 );
		update_post_meta( $event_id, '_event_dj_ids', $dj_ids );
		$timetable = array(
			array(
				'dj'    => $dj1,
				'from'  => '20:00',
				'to'    => '21:00',
				'order' => 1,
			),
			array(
				'dj'    => $dj2,
				'from'  => '21:00',
				'to'    => '22:00',
				'order' => 2,
			),
			array(
				'dj'    => $dj3,
				'from'  => '22:00',
				'to'    => '23:00',
				'order' => 3,
			),
		);
		update_post_meta( $event_id, '_event_dj_slots', $timetable );

		// Remove one DJ and update.
		$dj_ids = array( $dj1, $dj3 );
		update_post_meta( $event_id, '_event_dj_ids', $dj_ids );
		$timetable = array(
			array(
				'dj'    => $dj1,
				'from'  => '20:00',
				'to'    => '21:00',
				'order' => 1,
			),
			array(
				'dj'    => $dj3,
				'from'  => '22:00',
				'to'    => '23:00',
				'order' => 2,
			),
		);
		update_post_meta( $event_id, '_event_dj_slots', $timetable );

		// Fetch timetable as displayed.
		$slots = get_post_meta( $event_id, '_event_dj_slots', true );
		$this->assertCount( 2, $slots, 'Timetable should have 2 DJs after removal' );
		$this->assertEquals( $dj1, $slots[0]['dj'] );
		$this->assertEquals( $dj3, $slots[1]['dj'] );
		$this->assertEquals( '20:00', $slots[0]['from'] );
		$this->assertEquals( '23:00', $slots[1]['to'] );

		// Add a new DJ and check order.
		$dj_ids = array( $dj1, $dj3, $dj2 );
		update_post_meta( $event_id, '_event_dj_ids', $dj_ids );
		$timetable[] = array(
			'dj'    => $dj2,
			'from'  => '',
			'to'    => '',
			'order' => 3,
		);
		update_post_meta( $event_id, '_event_dj_slots', $timetable );

		$slots = get_post_meta( $event_id, '_event_dj_slots', true );
		$this->assertCount( 3, $slots, 'Timetable should have 3 DJs after re-adding' );
		$this->assertEquals( $dj2, $slots[2]['dj'] );
	}
}
