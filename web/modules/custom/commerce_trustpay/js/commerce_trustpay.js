(function ($, Drupal, drupalSettings, once) {
  'use strict';

  Drupal.behaviors.offsiteForm = {
    attach: function (context) {
      // TODO Implement once(): https://www.drupal.org/docs/drupal-apis/javascript-api/javascript-api-overview
      if (context !== document) {
        return;
      }
      console.log(context);
      var data = drupalSettings.commerce_trustpay;
      $('body').append("<iframe id=\"TrustPayFrame\" src=\"" + drupalSettings.commerce_trustpay.url + "\"></iframe>");
    }
  };

}(jQuery, Drupal, drupalSettings, once));
