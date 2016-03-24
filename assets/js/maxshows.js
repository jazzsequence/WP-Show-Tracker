jQuery(document).ready(function($) {
	console.log(maxshows.hidden_viewers);
	$.each(maxshows.hidden_viewers, function(index,viewer){
		$('input[value='+viewer+']').parent().hide();
	});
});