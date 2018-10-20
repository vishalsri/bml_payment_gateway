jQuery(function() {
	jQuery('body').on('updated_checkout', function(){
		jQuery('.payment_method_custom_payment .input-date').datepicker();
		jQuery('.payment_method_custom_payment .input-date').datepicker("option", "dateFormat", jQuery( '.payment_method_custom_payment .input-date' ).attr('data-dateformat'));
		jQuery('.payment_method_custom_payment .input-date').datepicker("setDate",jQuery( '.payment_method_custom_payment .input-date' ).attr('data-defaultdate'));

	});

});