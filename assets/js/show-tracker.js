jQuery(document).ready(function($) {
	if ( showtracker.hidden_viewers ) {
		// Hide viewers who've watched all their allowed shows.
		$.each(showtracker.hidden_viewers, function(index,viewer){
			$('input[value='+viewer+']').parent().fadeOut();
		});
	}

	// Show console log if debugging is active.
	if ( showtracker.wp_debug ) {
		console.log( showtracker.autosuggest );
	}

	if ( showtracker.autosuggest ) {
		// wp_localize_script will convert our array into an object. We need to convert it back to an array.
		showtracker.autosuggest = $.map( showtracker.autosuggest, function( value, index ){
			return [value];
		});

		// Log the autosuggest array after we've modified it.
		if ( showtracker.wp_debug ) {
			console.log( showtracker.autosuggest );
		}

		var post_title = $('input#submitted_post_title');
		post_title.autocomplete({
			source: showtracker.autosuggest,
		});
	}
});