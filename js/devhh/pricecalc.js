function getPriceCalcBlock() {
    var msg = jQuery('#product_addtocart_form').serialize();
    var url = '/price-calculator/index/index';
    var followingDiv = jQuery('.box-additional');
    var calCulator = jQuery('#price-calculator');

    calCulator.addClass('overlay');
    jQuery.ajax({
        type: 'POST',
        url: url,
        data: msg,
        success: function (data) {
            var count = (data.match(/<tr/g) || []).length;
            followingDiv.css('margin-top', (count - 1) * 45);
            calCulator.html(data).removeClass('overlay');
        }
    });
}

jQuery(document).ready(function () {
    getPriceCalcBlock();

    jQuery('.options-list input[type="radio"], .options-list input:checkbox, ' +
        '#cl_submit_price,  .option .swatch a').on('click', function () {
        getPriceCalcBlock();
    });

    jQuery('select.product-custom-option, select.qty')
        .on('change', function () {
            getPriceCalcBlock();
        });

    jQuery('#qty').on('keyup mouseup', function () {
        getPriceCalcBlock();
    });

    // make calculator stick when page gets scrolled
    if (jQuery(document).width() >= 775 && jQuery('#price-calculator').length > 0) {
        jQuery('#price-calculator').scrollToFixed();
    }
});