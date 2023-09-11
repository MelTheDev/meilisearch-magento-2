require(['jquery', 'mage/translate'],
	function ($) {
		if ($('#melthedev_meilisearch_instant_instant_facets').length > 0) {
			var addButton = $('#melthedev_meilisearch_instant_instant_facets tfoot .action-add');
			addButton.on('click', function(){
				handleFacetQueryRules();
			});
			$("#row_melthedev_meilisearch_instant_instant_facets .value").width('60%');
			handleFacetQueryRules();
		}

		function handleFacetQueryRules() {
			var facets = $('#melthedev_meilisearch_instant_instant_facets tbody tr');

			for (var i=0; i < facets.length; i++) {
				var rowId = $(facets[i]).attr('id');
				var searchableSelect = $('select[name="groups[instant][fields][facets][value][' + rowId + '][searchable]"]');

				searchableSelect.on('change', function(){
					configQrFromSearchableSelect($(this));	
				});

				configQrFromSearchableSelect(searchableSelect);	
			}
		}

		function configQrFromSearchableSelect(searchableSelect) {
			var rowId = searchableSelect.parent().parent().attr('id');
			var qrSelect = $('select[name="groups[instant][fields][facets][value][' + rowId + '][create_rule]"]');
			if (qrSelect.length > 0) {
				if (searchableSelect.val() == "2") {
					qrSelect.val('2');
					qrSelect.attr('disabled','disabled');
				} else {
					qrSelect.removeAttr('disabled');
				}
			} else {
				$('#row_melthedev_meilisearch_instant_instant_facets .melthedev_meilisearchblock').hide();
			}
		}

	}
);	
