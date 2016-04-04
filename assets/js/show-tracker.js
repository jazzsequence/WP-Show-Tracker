jQuery(document).ready(function($) {
	if ( showtracker.hidden_viewers ) {
		$.each(showtracker.hidden_viewers, function(index,viewer){
			$('input[value='+viewer+']').parent().fadeOut();
		});
	}

	console.log( showtracker.autosuggest );
	if ( showtracker.autosuggest ) {
		showtracker.autosuggest = $.map( showtracker.autosuggest, function( value, index ){
			return [value];
		});
		console.log( showtracker.autosuggest );
		var post_title = $('input#submitted_post_title');
		post_title.autocomplete({
			source: showtracker.autosuggest,
		});
	}
});