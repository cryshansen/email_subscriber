(function ($, Drupal) {
	
  Drupal.behaviors.testFormReset = {
    attach: function (context, settings) {
		
      // Custom reset form function to avoid issues.
      $.fn.customResetForm = function (formSelector) {
		
		var $form = $(formSelector).find('form');		
		  
        if ($form.length) {
          // Reset the form using jQuery's reset method on the DOM element.
          $form[0].reset();
		  
        } else {
			
          console.error('Form not found:', formSelector);
		  
        }
		
      };
    
	
	}	
  };
  
  
})(jQuery, Drupal);
