var $ = require('jquery');

$(function() {
  var $form = $('#social_feeds_settings');
  var $startDate = $form.find('input[name="instagram_sync_start"]');
  var $syncButton =  $form.find('button[name="instagram_sync_now_button"]');


  if(!Modernizr.inputtypes.date) {
    $startDate.datepicker();
  }

  $startDate.on('change', function() {
    if($(this).val() === '') {
      $syncButton.attr('disabled', 'disabled');
    } else {
      $syncButton.removeAttr('disabled');
    }
  });

  $syncButton.on('click', function(event) {
    var formData = $form.serializeArray();

    formData.push({
      'name': 'instagram_sync_now',
      'value': $startDate.val()
    });

    $.ajax({
      type: 'POST',
      data: formData,
      success: function(data) {
        console.log(data);

        if(data.updated) {
          $syncButton.text('Sync Finished');
        }
      },
      error: function(data) {
        $syncButton.text('Sync Failed');
      }
    });

    $syncButton.attr('disabled', 'disabled').text('Syncing...');
  });
});
