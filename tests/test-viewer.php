<?php

class WPST_Viewer_Test extends WP_UnitTestCase {

	function test_sample() {
		// replace this with some actual testing code
		$this->assertTrue( true );
	}

	function test_class_exists() {
		$this->assertTrue( class_exists( 'WPST_Viewer') );
	}

	function test_class_access() {
		$this->assertTrue( ()->viewer instanceof WPST_Viewer );
	}

  function test_taxonomy_exists() {
    $this->assertTrue( taxonomy_exists( 'wp-show-tracker-viewer' ) );
  }
}
