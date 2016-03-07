<?php

class WPST_Wpsc_show_Test extends WP_UnitTestCase {

	function test_sample() {
		// replace this with some actual testing code
		$this->assertTrue( true );
	}

	function test_class_exists() {
		$this->assertTrue( class_exists( 'WPST_Wpsc_show') );
	}

	function test_class_access() {
		$this->assertTrue( ()->wpsc-show instanceof WPST_Wpsc_show );
	}

  function test_cpt_exists() {
    $this->assertTrue( post_type_exists( 'wp-show-tracker-wpsc-show' ) );
  }
}
