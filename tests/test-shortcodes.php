<?php

class WPST_Shortcodes_Test extends WP_UnitTestCase {

	function test_class_exists() {
		$this->assertTrue( class_exists( 'WPST_Shortcodes') );
	}

	function test_class_access() {
		$this->assertTrue( wpst()->shortcodes instanceof WPST_Shortcodes );
	}
}
