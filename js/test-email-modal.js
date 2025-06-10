(function ($, Drupal) {
  Drupal.behaviors.openModal = {
    attach: function (context, settings) {
      // Show the modal when the button is clicked
      $('#open-modal-button').once('openModal').click(function (e) {
        $('#test-form-modal').modal('show');
      });

      // Define a function to close the modal
      window.closeModal = function (modalId) {
        $(modalId).modal('hide');
      };
    }
  };
})(jQuery, Drupal);


