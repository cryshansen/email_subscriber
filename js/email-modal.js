(function ($, Drupal) {
	//This file and function directly related to the form fields
  Drupal.behaviors.emailModalFormReset = {
    attach: function (context, settings) {
		
        window.emailModalFormReset = function (formId) {
        // Use the form's id to select the form directly.
        var $form = $('#' + formId);
        if ($form.length) {
          $form[0].reset();  // Reset the form using the native DOM reset method.
		      $form.find('input[type=text],input[type=email], textarea').val('');  // Reset only the input fields
		      console.log('Form reset', formId);
			
			//call the close modal here. :)
        } else {
          console.error('Form not found with id:', formId);
        }
      };
    
	
	}	
  };
  
  
})(jQuery, Drupal);
