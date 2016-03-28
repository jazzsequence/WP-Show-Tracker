jQuery(document).ready(function($) {
	if ( showtracker.hidden_viewers ) {
		$.each(showtracker.hidden_viewers, function(index,viewer){
			$('input[value='+viewer+']').parent().fadeOut();
		});
	}

	$('input#submitted_post_title').suggest(showtracker.admin_ajax + '?action=wpst_suggest');
	console.log(showtracker.admin_ajax);
	console.log($('input#submitted_post_title').suggest(showtracker.admin_ajax + '?action=autosuggest'));
});