/*!
 * Simpul Functions

 */

jQuery(document).ready(function($) {
	
	jQuery(document).on('mouseover', '.simpul-calendar-clickable', function() {
		jQuery(this).children('.simpul-calendar-cell').show();
	});
	jQuery(document).on('mouseout', '.simpul-calendar-clickable', function() {
		jQuery(this).children('.simpul-calendar-cell').hide();
	});
	
});