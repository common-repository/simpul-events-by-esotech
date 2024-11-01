jQuery(document).ready(function() {
	
	if( jQuery('.simpul-calendar').length != 0 ) {
	
		var dataLeft = {};
		var dataRight = {};
		var simpulEventsWidgetID = jQuery('.simpul-events').attr('id').split('-').pop();
		
	    function updateDate() {
	    	switch(jQuery('.simpul-calendar').attr('month')) {
	    		case '12':
	    			dataRight = {
			    		
				    	// here we declare the parameters to send along with the request
				        // this means the following action hooks will be fired:
				        // wp_ajax_nopriv_myajax-submit and wp_ajax_myajax-submit
				        action : SimpulAjax.ajaxcall,
				 
				        // other parameters can be added along with "action"
				        //postID : simpulAjax.postID
				    	year: Number(jQuery('.simpul-calendar').attr('year')) + 1, 
				        month: 1,
				        widgetID: simpulEventsWidgetID
			        
			        }
			        dataLeft = {
			    		
				    	// here we declare the parameters to send along with the request
				        // this means the following action hooks will be fired:
				        // wp_ajax_nopriv_myajax-submit and wp_ajax_myajax-submit
				        action : SimpulAjax.ajaxcall,
				 
				        // other parameters can be added along with "action"
				        //postID : simpulAjax.postID
				    	year: Number(jQuery('.simpul-calendar').attr('year')), 
				        month: Number(jQuery('.simpul-calendar').attr('month')) - 1,
				        widgetID: simpulEventsWidgetID
			        
			        }
	    			break;
	    		case '1':
	    			dataLeft = {
				    		
						// here we declare the parameters to send along with the request
				        // this means the following action hooks will be fired:
				        // wp_ajax_nopriv_myajax-submit and wp_ajax_myajax-submit
				        action : SimpulAjax.ajaxcall,
				 
				        // other parameters can be added along with "action"
				        //postID : simpulAjax.postID
				    	year: Number(jQuery('.simpul-calendar').attr('year') - 1), 
				        month: 12,
				        widgetID: simpulEventsWidgetID
			        
		        	}
		        	dataRight = {
				    		
						// here we declare the parameters to send along with the request
				        // this means the following action hooks will be fired:
				        // wp_ajax_nopriv_myajax-submit and wp_ajax_myajax-submit
				        action : SimpulAjax.ajaxcall,
				 
				        // other parameters can be added along with "action"
				        //postID : simpulAjax.postID
				    	year: Number(jQuery('.simpul-calendar').attr('year')), 
				        month: Number(jQuery('.simpul-calendar').attr('month')) + 1,
				        widgetID: simpulEventsWidgetID
				        
			        }
	    			break;
	    		default:
	    			dataRight = {
				    		
						// here we declare the parameters to send along with the request
				        // this means the following action hooks will be fired:
				        // wp_ajax_nopriv_myajax-submit and wp_ajax_myajax-submit
				        action : SimpulAjax.ajaxcall,
				 
				        // other parameters can be added along with "action"
				        //postID : simpulAjax.postID
				    	year: Number(jQuery('.simpul-calendar').attr('year')), 
				        month: Number(jQuery('.simpul-calendar').attr('month')) + 1,
				        widgetID: simpulEventsWidgetID
				        
			        }
			        dataLeft = {
				    		
						// here we declare the parameters to send along with the request
				        // this means the following action hooks will be fired:
				        // wp_ajax_nopriv_myajax-submit and wp_ajax_myajax-submit
				        action : SimpulAjax.ajaxcall,
				 
				        // other parameters can be added along with "action"
				        //postID : simpulAjax.postID
				    	year: Number(jQuery('.simpul-calendar').attr('year')), 
				        month: Number(jQuery('.simpul-calendar').attr('month')) - 1,
				        widgetID: simpulEventsWidgetID
				        
			        }
	    			break;
	    	}    	
		    return;
	    }
	    
	    updateDate();
		
		// Right
		
		jQuery(document).on('click', '.simpul-calendar-header-right',
		
			function () {
			    
			    updateDate();
			    
			    jQuery.post(
				    SimpulAjax.ajaxurl,
				    dataRight,
				    function(result) {
	
				    	jQuery('.simpul-calendar').each(function() {
				    		jQuery(this).remove();
				    	});
				    	jQuery('.calendar-anchor').prepend(result);
				    	
			    	}
				)
				
				return false;
			    
		   }
		
		);
		
		// Left
		
		jQuery(document).on('click', '.simpul-calendar-header-left',
		
			function () {
			    
			    updateDate();
			    
			    jQuery.post(
				    SimpulAjax.ajaxurl,
				    dataLeft,
				    function(result) {
	
				    	jQuery('.simpul-calendar').each(function() {
				    		jQuery(this).remove();
				    	});
				    	jQuery('.calendar-anchor').prepend(result);
				    	
			    	}
				)
				
				return false;
			    
		   }
		
		);
	
	}
	
});