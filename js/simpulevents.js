/*!
 * Simpul Functions

 */

jQuery(document).ready(function($) {
	var d = new Date();
	//Add Date Picker
	jQuery(".hasDatePicker").click( function() {
	
		jQuery(this).datepicker().datepicker( "show" );	 // Calls Datepicker then also Shows it on First Click
	
	});
	
	jQuery(".hasDateTimePicker").click( function() {
	
		jQuery(this).datetimepicker({
			dateFormat: 'yy-m-d', 
			ampm: true, 
			hourGrid: 6,
			minuteGrid: 15, 
			hour: 00,
			minute: 6,
			stepMinute: 15
		}).datetimepicker( "show" );	 // Calls Datepicker then also Shows it on First Click
	
	});
	//Timer 
	var typewatch = ( function() {
		var timer = 0;
		return function(callback, ms) {
			clearTimeout (timer);
			timer = setTimeout(callback, ms);
  		}  
	})();
	
	//Type for Google Maps
	jQuery(".hasMap").keyup( function() {
		var iframeid = jQuery(this).attr('id');
		var address = this.value;
		address = address.replace(/ /gi, '+');
		
		typewatch( function () {
			jQuery("." + iframeid ).each( function() {
				jQuery( this ).after('<iframe class="' + iframeid + '" width="240" height="150" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="http://maps.google.com/maps?q=' + address + '&output=embed"></iframe>');
				jQuery( this ).remove();
    			
			});
    		
  		}, 2000 );// executed only 5000 ms after the last keyup event.
	});
	
	
});