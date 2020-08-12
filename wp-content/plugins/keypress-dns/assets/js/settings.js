( function ( $ ) {

	$( document ).ready( function () {

		var hasCredentialsUnsaved = false;

		$( '#kpdns-settings-provider' ).on('focusin', function(){
			$(this).data('val', $(this).val());
		});

		$( '#kpdns-settings-provider' ).change( function( e ) {

			var createNewEncryptionKeyBtn = $('#kpdns-create-new-encryption-key-btn');

			if ( this.value != -1 ) {
				var spinner = $( '#kpdns-provider-spinner' );
				var submitBtn = $( '#submit' );

				if ( createNewEncryptionKeyBtn.length ) {
					createNewEncryptionKeyBtn.show();
					createNewEncryptionKeyBtn.attr('disabled', 'disabled');
				}

				submitBtn.attr('disabled', 'disabled');
				spinner.addClass("is-active");
				$.post(
					ajaxurl,
					{
						_wpnonce   : kpdnsSettings.nonces.getProviderCredentials,
						action     : kpdnsSettings.actions.getProviderCredentials,
						provider   : this.value,
						page       : kpdnsSettings.page
					},
					function( response ) {
						spinner.removeClass("is-active");
						submitBtn.removeAttr('disabled');
						if ( createNewEncryptionKeyBtn.length ) {
							createNewEncryptionKeyBtn.removeAttr('disabled');
						}
						$('#kpdns-credentials').html(response);
					}
				);
			} else {
				if ( createNewEncryptionKeyBtn.length ) {
					createNewEncryptionKeyBtn.hide();
				}
				$('#kpdns-credentials').html('');
			}
		} );

		$( '#kpdns-create-new-encryption-key-btn' ).click( function() {
			var createNewEncryptionKeyBtn = $( this );
			var submitBtn   = $( '#submit' );

			createNewEncryptionKeyBtn.attr('disabled', 'disabled');
			submitBtn.attr('disabled', 'disabled');

			$.post(
				ajaxurl,
				{
					_wpnonce : kpdnsSettings.nonces.createNewEncryptionKey,
					action   : kpdnsSettings.actions.createNewEncryptionKey,
					page     : kpdnsSettings.page
				},
				function( response ) {
					kpdnsRenderKeyDialog( 'create-new-key', response[ kpdnsSettings.params.key ] );
					createNewEncryptionKeyBtn.removeAttr('disabled');
					submitBtn.removeAttr('disabled');
				}
			);
		} );

		$( '#kpdns-settings\\[credentials\\]' ).change( function() {
			hasCredentialsUnsaved = !! this.value;
		} );

		$( '#kpdns-provider-settings-form' ).submit( function( e ) {
			var message = 'If you click OK, all credentials stored in the database will be deleted.';
			if ( $( '#kpdns-settings\\[provider\\]' ).val() == -1 && ! confirm( message ) ) {
				e.preventDefault();
			}
		} );

		if ( kpdnsSettings.options.displaySaveSettingsKeyDialog ) {
			kpdnsRenderKeyDialog( 'save-settings-key', kpdnsSettings.options.key );
		}

		var mappingMethod = $('.kpdns-mapping-method-radio-btn');
		if ( mappingMethod.length ) {
			var metaboxAfterInput                       = $('#kpdns-settings-wu-metabox-after-input'),
				modalboxText				            = $('#kpdns-settings-wu-modalbox-text'),
				metaboxAfterInputARecordPlaceholder     = metaboxAfterInput.data('a-record-placeholder'),
				metaboxAfterInputProviderNSPlaceholder  = metaboxAfterInput.data('provider-ns-placeholder'),
				metaboxAfterInputCustomNSPlaceholder    = metaboxAfterInput.data('custom-ns-placeholder'),
				modalboxAfterInputARecordPlaceholder    = modalboxText.data('a-record-placeholder'),
				modalboxAfterInputProviderNSPlaceholder = modalboxText.data('provider-ns-placeholder'),
				modalboxAfterInputCustomNSPlaceholder   = modalboxText.data('custom-ns-placeholder');

			mappingMethod.change( function(e) {

				switch( $(this).val()) {
					case 'a-record':
						metaboxAfterInput.attr('placeholder', metaboxAfterInputARecordPlaceholder);
						modalboxText.attr('placeholder', modalboxAfterInputARecordPlaceholder);
						break;
					case 'provider-ns':
						metaboxAfterInput.attr('placeholder', metaboxAfterInputProviderNSPlaceholder);
						modalboxText.attr('placeholder', modalboxAfterInputProviderNSPlaceholder);
						break;
					case 'custom-ns':
						metaboxAfterInput.attr('placeholder', metaboxAfterInputCustomNSPlaceholder);
						modalboxText.attr('placeholder', modalboxAfterInputCustomNSPlaceholder);
						break;
				}
			});
		}


		function kpdnsRenderKeyDialog( type, key ) {
			switch( type ) {

				case 'save-settings-key':
					var dialog = '';
					dialog += '<div id="kpdns-save-settings-key-dialog" class="kpdns-dialog" title="" style="display: none">';
					dialog += '<p>For security purposes, your credentials have stored in the WordPress database encrypted with a secret key.</p>';
					dialog += '<p>Please copy and paste the line of code below to your wp-config.php file and then click on "Check Key" button.</p>';
					dialog += '<code>define( \'KPDNS_ENCRYPTION_KEY\', \'' + key + '\' );</code>';
					dialog += '<p>Warning: The encryption key is only created once. If you don\'t add it now to your wp-config.php file, you must create a new one by clicking on the "Create New Encryption Key" button.</p>';
					dialog += '<p class="response-message"></p>';
					dialog += '</div>';
					$(dialog).appendTo('body');
					break;

				case 'create-new-key' :
					var dialog = '';
					dialog += '<div id="kpdns-create-new-key-dialog" class="kpdns-dialog" title="" style="display: none">';
					dialog += '<p>A new encryption key has been created and all your credentials have been re-encrypted.</p>';
					dialog += '<p>Please copy and paste the line of code below to your wp-config.php file and then click on "Check Key" button. Don\'t forget to remove the old key definition.</p>';
					dialog += '<code>define( \'KPDNS_ENCRYPTION_KEY\', \'' + key + '\' );</code>';
					dialog += '<p>Warning: The encryption key is only created once. If you don\'t add it now to your wp-config.php file, you must create a new one by clicking on the "Create New Encryption Key" button.</p>';
					dialog += '<p class="response-message"></p>';
					dialog += '</div>';
					$(dialog).appendTo('body');
					break;
			}

			var id = 'kpdns-' + type + '-dialog'
			var dialogContent = $( '#' + id );

			dialogContent.dialog( {
				modal: true,
				dialogClass: "no-close",
				buttons: kpdnsGetKeyDialogButtons( dialogContent, key ),
				create: function( event, ui ) {
					dialogContent.parent().find( '.ui-dialog-buttonpane button' ).addClass( 'button button-primary' );
					dialogContent.parent().find( '.kpdns-check-key-ok-btn' ).hide();
				}
			});
		}

		function kpdnsGetKeyDialogButtons( dialogContent, key ) {
			var buttons = [
				{
					text: 'Check Key',
					'class': 'kpdns-check-key-btn',
					click: function() {
						var dialogParent = dialogContent.parent(),
							spinner = $( '<span class="spinner is-active"></span>' ),
							checkKeyBtn = dialogParent.find( ' .kpdns-check-key-btn' );

						dialogParent.find('.ui-dialog-buttonset').append( spinner );
						checkKeyBtn.attr('disabled', 'disabled');

						var data = {};
						data._wpnonce = kpdnsSettings.nonces.checkDefinedKey;
						data.action   = kpdnsSettings.actions.checkDefinedKey;
						data.page     = kpdnsSettings.page;
						data[ kpdnsSettings.params.key ] = key;

						$.post(
							ajaxurl,
							data,
							function( response ) {
								var responseMessage = dialogContent.find( '.response-message' );
								responseMessage.text( response.message );
								if ( response.success ) {
									responseMessage.removeClass('kpdns-message-error');
									responseMessage.addClass('kpdns-message-success');
									dialogParent.find( ' .kpdns-check-key-btn').remove();
									dialogParent.find( ' .kpdns-check-key-ok-btn').show();
								} else {
									responseMessage.addClass('kpdns-message-error');
								}
								spinner.remove();
								checkKeyBtn.removeAttr('disabled');
							}
						);
						//$( this ).dialog( 'close' );
					}
				},
				{
					text: 'OK',
					class: 'kpdns-check-key-ok-btn',
					click: function() {
						$(this).dialog("close");
					}
				}
			];
			return buttons;
		}
	} );

})(jQuery);