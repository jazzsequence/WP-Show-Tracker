<?php

class WPST_Deprecated_Test extends WP_UnitTestCase {

	function test_sample() {
		// replace this with some actual testing code
		$this->assertTrue( true );
	}

	function test_class_exists() {
		$this->assertTrue( class_exists( 'WPST_Deprecated') );
	}

	function test_class_access() {
		$this->assertTrue( wpst()->deprecated instanceof WPST_Deprecated );
	}
}
