(function ($, Drupal) {
	
  Drupal.behaviors.testFormReset = {
    attach: function (context, settings) {
		
        $.fn.testFormReset = function (formId) {
        // Use the form's id to select the form directly.
        var $form = $('#' + formId);
        if ($form.length) {
          $form[0].reset();  // Reset the form using the native DOM reset method.
		   $form.find('input[type=text],input[type=email], textarea').val('');  // Reset only the input fields
        } else {
          console.error('Form not found with id:', formId);
        }
      };
    
	
	}	
  };
  
  
})(jQuery, Drupal);
