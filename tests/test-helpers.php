<?php

class WPST_Helpers_Test extends WP_UnitTestCase {

	function test_sample() {
		// replace this with some actual testing code
		$this->assertTrue( true );
	}

	function test_class_exists() {
		$this->assertTrue( class_exists( 'WPST_Helpers') );
	}

	function test_class_access() {
		$this->assertTrue( ()->helpers instanceof WPST_Helpers );
	}
}