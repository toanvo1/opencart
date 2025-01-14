/**
 * 2018 Paysera
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to support@paysera.com so we can send you a copy immediately.
 *
 *  @author    Paysera <plugins@paysera.com>
 *  @copyright 2018 Paysera
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of Paysera
 */

$(document).ready(function() {
    $('select[name="category"]').on('click',function() {
        var selectedOptionId = $(this).val();
        var selectedIndexs = $(this).prop('selectedIndex');
        var selectedOptionLabel = $(this).find(":selected").text();

        if (selectedIndexs > 0){
            $('#paysera-category' + selectedOptionId).remove();

            $('#paysera-category').append(
                '<div id="paysera-category'
                + selectedOptionId
                + '"><i class="fa fa-minus-circle"></i> '
                + selectedOptionLabel
                + '<input type="hidden" name="payment_paysera_category[]" value="'
                + selectedOptionId
                + '" /></div>'
            );
        }
    });

    $('#paysera-category').delegate('.fa-minus-circle', 'click', function() {
        $(this).parent().remove();
    });
});

