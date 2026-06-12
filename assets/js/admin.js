/**
 * Admin JavaScript for Olkoo Payment OS
 *
 * @package OlkooPaymentOS
 * @since 1.0.0
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Copy webhook URL to clipboard
        $('.olkoo-copy-webhook-url').on('click', function(e) {
            e.preventDefault();

            var $button = $(this);
            var webhookUrl = $button.data('url');

            // Create temporary input element
            var $temp = $('<input>');
            $('body').append($temp);
            $temp.val(webhookUrl).select();
            document.execCommand('copy');
            $temp.remove();

            // Show feedback
            var originalText = $button.text();
            $button.text('Copied!');

            setTimeout(function() {
                $button.text(originalText);
            }, 2000);
        });

        // Toggle test mode fields visibility
        $('input[name*="test_mode"]').on('change', function() {
            var $row = $(this).closest('tr');
            var $testFields = $row.nextUntil('tr:not([class*="test_"])');

            if ($(this).is(':checked')) {
                $testFields.show();
            } else {
                $testFields.hide();
            }
        }).trigger('change');
    });

})(jQuery);
