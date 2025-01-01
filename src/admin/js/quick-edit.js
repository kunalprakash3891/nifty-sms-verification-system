jQuery(document).ready(function ($) {
	$('.editinline').on('click', function (e) {
		e.preventDefault();

		var $row = $(this).closest('tr');
		var id = $(this).data('id');
		var $inlineData = $('#inline-' + id);
		var phone_number = $inlineData.find('.phone_number').text().trim();
		var notes = $inlineData.find('.notes').text().trim();

		var $quickEditRow = $('#quick-edit-row');
		if ($quickEditRow.length === 0) {
			var template = wp.template('quick-edit-template');
			$quickEditRow = $(template());
		}

		$quickEditRow.insertAfter($row);

		$('#quick-edit-phone-number').val(phone_number);
		$('#quick-edit-notes').val(notes);

		$('#quick-edit-save').off('click').on('click', function () {
			var newPhoneNumber = $('#quick-edit-phone-number').val();
			var newNotes = $('#quick-edit-notes').val();

			$.post(NiftySVSBlacklistQuickEdit.ajax_url, {
				action: 'niftysvs_blacklist_quick_edit',
				nonce: NiftySVSBlacklistQuickEdit.nonce,
				id: id,
				phone_number: newPhoneNumber,
				notes: newNotes
			}, function (response) {
				if (response.success) {
					$row.find('td.column-phone_number .phone-number-text').text(newPhoneNumber);
					$row.find('td.column-notes').text(newNotes);
					$inlineData.find('.phone_number').text(newPhoneNumber);
					$inlineData.find('.notes').text(newNotes);
					$quickEditRow.remove();
				} else {
					alert(response.data);
				}
			});

			return false;
		});

		$('#quick-edit-cancel').off('click').on('click', function () {
			$quickEditRow.remove();
			return false;
		});
	});
});