jQuery(document).ready(function($) {
	$.each(maxshows.hidden_viewers, function(index,viewer){
		$('input[value='+viewer+']').parent().fadeOut();
	});
});