(function( $ ){
	
	var unit_all = function(field_id){

		if(typeof field_id === 'undefined')
			return;	
			
		var field_value = $('input.cmb2-unit-input[data-field-id="' + field_id + '"]').val();
		var field_unit = $('select.cmb2-unit-select[data-field-id="' + field_id + '"]').val();	
		
		if(field_value !== ''){
			$('input[type=hidden][data-field-id="' + field_id + '"]').val(field_value + field_unit);	
		}else $('input[type=hidden][data-field-id="' + field_id + '"]').val('');
			
	};
	
	$('select.cmb2-unit-select').on('change', function(){
		unit_all($(this).attr('data-field-id'));
	});
	
	$('input.cmb2-unit-input').on('input propertychange paste', function(){
		unit_all($(this).attr('data-field-id'));		
	});
	
})( jQuery );
