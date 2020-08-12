( function ( $ ) {

	$(document).ready(function(){
		$( '.kpdns-tooltip' ).tooltip( {
			content: function () {
				return $(this).prop('title');
			},
			tooltipClass: "kpdns-tooltip-text",
			position: {
				my: 'center top',
				at: 'center bottom+10',
				collision: 'flipfit'
			},
			hide: {
				duration: 500
			},
			show: {
				duration: 500
			}
		});
	});
})(jQuery);