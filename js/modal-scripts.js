(function ($, Drupal) {
  Drupal.behaviors.modalForm = {
    attach: function (context, settings) {
      $('#open-modal').once('modalForm').on('click', function () {
        $('#modal').show();
      });

      $('.close-modal').once('modalForm').on('click', function () {
        $('#modal').hide();
      });
    }
  };
  
  Drupal.AjaxCommands.prototype.closeModal = function (ajax, response, status) {
    $('#modal').hide();
  };

})(jQuery, Drupal);
