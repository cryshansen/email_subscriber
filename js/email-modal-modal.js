(function ($, Drupal) {
    //this file and function is directly related to the modal functions
  Drupal.behaviors.openEmailModal = {
    attach: function (context, settings) {
       
      // Show the modal when the button is clicked
      $('#email-modal-button').once('openEmailModal').click(function (e) {
        $('#email-form-modal').modal('show');
      });

      // Define a function to close the modal
      window.closeModal = function (modalId) {
        $(modalId).modal('hide');
      };
    }
  };
})(jQuery, Drupal);


console.log('Drupal behavior attached');