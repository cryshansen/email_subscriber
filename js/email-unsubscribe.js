(function ($, Drupal) {
  Drupal.behaviors.emailUnsubscribeFormReset = {
      
    attach: function (context, settings) {
      $.fn.emailUnsubscribeFormReset = function (formId) {
        console.log('Drupal behavior attached');
        var $form = $('#' + formId);
        alert($form);
        if ($form.length) {
          $form[0].reset();  // Reset the form using the native DOM reset method.
          $form.find('input[type=text],input[type=email], textarea').val('');  // Clear input fields
          console.log('Form reset', formId);
          // Call any other functions (like closing a modal) here.
        } else {
          console.error('Form not found with id:', formId);
        }
      };
    }
  };
})(jQuery, Drupal);

console.log('Drupal behavior attached');