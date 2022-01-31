"use strict";

(function ($) {
  var cryptoPrice = function cryptoPrice() {
    $('.crypto-price .crypto-currency').each(function () {
      $(this).on('mouseover', function () {
        $(this).siblings('.crypto-currency').removeClass('active');
        $(this).addClass('active');
        $(this).parent().siblings('.crypto-amount-container').children('#crypto-amount').html($(this).data('price') + ' ' + $(this).data('currency'));
      });
    });
    $('a.woocommerce-LoopProduct-link').on('click', function (event) {
      if ($(event.target).hasClass('crypto-currency')) {
        event.preventDefault();
      }
    });
  };

  $(document).on('show_variation updated_cart_totals updated_checkout', cryptoPrice);
  cryptoPrice();
})(jQuery);