	( function ( $ ) {

	$( document ).ready( function () {

		/** Zones List / Delete Zone **/
		$( '.kpdns-delete-zone' ).click( function(e) {
			e.preventDefault();

			var url  = $(this).attr('href'),
				id   = 'kpdns-delete-zone-dialog',
				text = 'You are about to delete DNS zone <strong>' + $(this).data('zone') + '</strong>',
				type = 'zone';

			kpdnsRenderConfirmDialog( id, url, type, text );

		} );

		/** Zones List / Bulk actions **/
		$( '.bulk-action-selector' ).change( function(e) {
			var selected  = $(this).val(),
				container = $(this).parents('.bulkactions-fields'),
				input     = container.find('.kpdns-record-value');

			if ( input.length ) {
				input.remove();
			}


			switch ( selected ) {
				case kpdnsZones.bulkActions.updateARecords:
					input = kpdnsGetBulkActionInput( kpdnsZones.ipv4Placeholder );
					container.append(input);
					break;

				case kpdnsZones.bulkActions.updateAAAARecords:
					input = kpdnsGetBulkActionInput( kpdnsZones.ipv6Placeholder );
					container.append(input);
					break;
			}

		} );

		/** Edit Zone / Delete Record **/
		$( '.kpdns-delete-record' ).click( function(e) {
			e.preventDefault();

			var url  = $(this).attr('href'),
				id   = 'kpdns-delete-record-dialog',
				text = 'You are about to delete record <strong>' + $(this).data('record') + '</strong>',
				type = 'record';

			kpdnsRenderConfirmDialog( id, url, type, text );

		} );

		/** Add Zone / Pull Records **/
		$('#kpdns-zone-domain').bind( 'focus input propertychange', function(e) {

			return;

			var domain  = $(this).val();
			var spinner = $( '#kpdns-add-zone-domain-spinner' );

			if ( isValidDomain( domain ) ) {
				if ( $(this).hasClass('kpdns-input-invalid') ) {
					$(this).removeClass('kpdns-input-invalid')
				}

				if ( ! $(this).hasClass('kpdns-input-valid') ) {
					$(this).addClass('kpdns-input-valid');
				}

				if ( ! spinner.hasClass('is-active') ) {
					spinner.addClass('is-active');
				}

				var data    = {
					'action'   : kpdnsZones.pullRecords.action,
					'nonce'    : kpdnsZones.pullRecords.nonce,
					'domain'   : domain,
				}

				console.log('Domain: ' + domain );

				$.post( kpdnsZones.pullRecords.url, data, function( response ) {
					console.log(response);
					$('#kpdns-records-container').text(response);
				});

			} else {
				if ( $(this).hasClass('kpdns-input-valid') ) {
					$(this).removeClass('kpdns-input-valid')
				}

				if ( ! $(this).hasClass('kpdns-input-invalid') ) {
					$(this).addClass('kpdns-input-invalid');
				}

				if (spinner.hasClass('is-active') ) {
					spinner.removeClass('is-active');
				}
			}
		});

		/** Add Zone / Add Record **/
		$('#kpdns-record-type').change( function(){
			var recordType            = $(this).val(),
				recordTypeDescription = $(this).parent().find('.description'),
				recordTypeSelectDesc  = kpdnsZones.recordTypeSelectDesc,
				allRecordTypes        = kpdnsZones.recordTypes,
			    rdataFieldsContainer = $('#kpdns-record-rdata-fields'),
				submitButton		  = $('#kpdns-add-record-submit-btn');

			rdataFieldsContainer.empty();

			if( allRecordTypes[ recordType ] !== undefined ) {
				submitButton.removeAttr('disabled');
				recordTypeDescription.html( allRecordTypes[ recordType ]['description'] );
				Object.values( allRecordTypes[ recordType ]['rdata-fields'] ).forEach(function ( fieldData ) {
					var fieldRowContainer = $('<div/>');

					fieldRowContainer.addClass( 'kpdns-record-field-row-container' );

					if ( fieldData.label !== undefined ) {
						var labelContainer = $( '<div/>' ),
							label = $( '<label/>' );

						labelContainer.addClass( 'kpdns-record-label-container' );

						if ( fieldData.id !== undefined ) {
							label.attr( 'for', 'kpdns-record-' + fieldData.id );
						}

						label.text( fieldData.label );

						labelContainer.append( label );

						fieldRowContainer.append( labelContainer );
					}

					var fieldContainer    = $('<div/>');
					fieldContainer.addClass( 'kpdns-record-field-container' );

					if ( fieldData.fields !== undefined ) { // Multiple fields. Case TTL and TTL Units.
						Object.values(fieldData.fields).forEach( function ( fieldData ) {
							fieldContainer = kpdnsGetRecordFieldRow( fieldData, fieldContainer );
						});

					} else { // Single field.
						fieldContainer = kpdnsGetRecordFieldRow( fieldData, fieldContainer );
					}

					if ( fieldData.description !== undefined ) {
						var description = $( '<p/>' );
						description.addClass('description');
						description.text( fieldData.description );
						fieldContainer.append( description );
					}

					if ( ! kpdnsIsEmpty( fieldContainer ) ) {
						fieldRowContainer.append( fieldContainer );
						rdataFieldsContainer.append( fieldRowContainer );
					}
				});
			} else {
				recordTypeDescription.text( recordTypeSelectDesc );
				submitButton.attr('disabled', 'disabled');
			}
		});

		function kpdnsIsEmpty( element ) {
			return ! $.trim( element.html() );
		}

		function kpdnsGetRecordFieldRow( fieldData, fieldContainer ) {

				if ( fieldData.type === undefined || fieldData.id === undefined ) {
					return undefined;
				}

				var field;

				switch ( fieldData.type ) {
					case 'text':
						field = $( '<input/>' );
						field.attr( 'type', 'text' );
						field.attr( 'id', 'kpdns-record-' + fieldData.id );
						field.attr( 'name', 'record[' + fieldData.id + ']' );

						if ( fieldData.value !== undefined ) {
							field.val( fieldData.value );
						}

						if ( fieldData.placeholder !== undefined ) {
							field.attr( 'placeholder', fieldData.placeholder );
						}

						if ( fieldData.class !== undefined ) {
							field.addClass( fieldData.class );
						}

						if ( fieldData.maxlength !== undefined ) {
							field.attr( 'maxlength', fieldData.maxlength );
						}

						break;

					case 'textarea':
						field = $( '<textarea/>' );
						field.attr( 'id', 'kpdns-record-' + fieldData.id );
						field.attr( 'name', 'record[' + fieldData.id + ']' );

						if ( fieldData.value !== undefined ) {
							field.text( fieldData.value );
						}

						if ( fieldData.placeholder !== undefined ) {
							field.attr( 'placeholder', fieldData.placeholder );
						}

						if ( fieldData.class !== undefined ) {
							field.addClass( fieldData.class );
						}

						if ( fieldData.maxlength !== undefined ) {
							field.attr( 'maxlength', fieldData.maxlength );
						}

						if ( fieldData.rows !== undefined ) {
							field.attr( 'rows', fieldData.rows );
						}

						if ( fieldData.cols !== undefined ) {
							field.attr( 'cols', fieldData.cols );
						}

						break;

					case 'select' :
						field = $( '<select/>' );
						field.attr( 'id', 'kpdns-record-' + fieldData.id );
						field.attr( 'name', 'record[' + fieldData.id + ']' );

						if ( fieldData.class !== undefined ) {
							field.addClass( fieldData.class );
						}

						if ( fieldData.options !== undefined ) {
							Object.values( fieldData.options ).forEach( function ( option ) {
								var opt = $( '<option/>' );
								opt.attr('value', option.value);
								opt.text(option.text);
								field.append( opt );
							});
						}

						break;
				}

				if ( field === undefined ) {
					return field;
				}

				fieldContainer.append( field );

			if ( fieldData.id === 'name' ) {
				var zoneId = $( '#kpdns-zone-domain' ).val(),
					domain = $( '<span/>');

				domain.addClass('kpdns-record-name-domain');
				domain.text( '.' + zoneId );

				fieldContainer.append( domain );
			}

				return fieldContainer;
		}

		/**
		 *
		 * @param id
		 * @param url
		 * @param type
		 * @param text
		 */

		function kpdnsRenderConfirmDialog( id, url, type, text ) {

			switch ( type ) {
				case 'zone':
					var title ='Delete DNS Zone';
					var confirmBtnText = 'Yes, delete zone';
					break;

				case 'record':
					var title ='Delete record';
					var confirmBtnText = 'Yes, delete record';
					break;
			}

			var dialog = '';
			dialog += '<div id="' + id + '" class="kpdns-dialog" title="' + title + '" style="display: none">';
			dialog += '<p>' + text + '</p>';
			dialog += '<p>This action cannot be undone. Do you want to continue?</p>';
			dialog += '</div>';
			$(dialog).appendTo('body');

			var dialogContent = $( '#' + id );
			dialogContent.dialog( {
				modal: true,
				dialogClass: 'no-close',
				buttons: [
					{
						text: confirmBtnText,
						class: 'button button-primary',
						click: function() {
							window.location.replace(url);
						}
					},
					{
						text: 'Cancel',
						class: 'button button-secondary kpdns-dialog-cancel-btn',
						click: function() {
							$( this ).dialog( 'close' );
						}
					}
				]
			} );
		}

		function kpdnsGetBulkActionInput( placeholder ) {
			var input = $( '<input/>' )
				.attr( 'type', 'text' )
				.attr( 'name', 'value' )
				.attr( 'value', '' )
				.attr( 'placeholder', placeholder )
				.addClass( 'regular-text' )
				.addClass( 'kpdns-record-value' );

			return input;
		}

		function isValidDomain( domain ) {
			if (! domain ) {
				return false;
			}
			var re = /^(?!:\/\/)([a-zA-Z0-9-]+\.){0,5}[a-zA-Z0-9-][a-zA-Z0-9-]+\.[a-zA-Z]{2,64}?$/gi;
			return re.test( domain );
		}
	} );

})(jQuery);