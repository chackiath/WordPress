( function ( $ ) {

	$( document ).ready( function () {

		if ( kpdnsNameServers.dialogs.nsCreated.autoDisplay ) {
			kpdnsRenderGlueRecordsDialog();
		}

		/** Name Servers List / Delete Name Server **/
		$( '.kpdns-delete-ns' ).click( function(e) {
			e.preventDefault();

			var url    = $(this).attr('href'),
				NSName = $(this).data('name-server');

			if ( NSName === 'orphaned-ns' ) {
				kpdnsRenderConfirmDeleteOrphanedNSDialog( url);
			} else {
				kpdnsRenderConfirmDeleteDialog( url, NSName );
			}
		} );

		$('#kpdns-name-server-domain').on('change input', function(e){
			var domain = $(this).val();
			$( '.kpdns-ns-domain').each( function() {
				if ( '' !== domain ) {
					$(this).text( domain );
				} else {
					$(this).text( kpdnsNameServers.customNSDomainPlaceholder );
				}

			});
		} );

		function kpdnsRenderConfirmDeleteOrphanedNSDialog( url ) {
			var title              = kpdnsNameServers.dialogs.confirmDeleteOrphaned.title,
				id                 = kpdnsNameServers.dialogs.confirmDeleteOrphaned.id,
				innerHTML          = kpdnsNameServers.dialogs.confirmDeleteOrphaned.innerHTML,
				confirmButtonText  = kpdnsNameServers.dialogs.confirmDeleteOrphaned.confirmButtonText,
				cancelButtonText   = kpdnsNameServers.dialogs.confirmDeleteOrphaned.cancelButtonText;

			var dialog = '';
			dialog += '<div id="' + id + '" class="kpdns-dialog" title="' + title + '" style="display: none">';
			dialog += innerHTML;
			dialog += '</div>';
			$(dialog).appendTo('body');

			var dialogContent = $( '#' + id );
			dialogContent.dialog( {
				modal: true,
				dialogClass: 'no-close',
				buttons: [
					{
						text: confirmButtonText,
						class: 'button button-primary',
						click: function() {
							window.location.replace(url);
						}
					},
					{
						text: cancelButtonText,
						class: 'button button-secondary kpdns-dialog-cancel-btn',
						click: function() {
							$( this ).dialog( 'close' );
						}
					}
				]
			} );
		}

		function kpdnsRenderConfirmDeleteDialog( url, NSName ) {
			var title              = kpdnsNameServers.dialogs.confirmDelete.title,
				id                 = kpdnsNameServers.dialogs.confirmDelete.id,
				innerHTML          = kpdnsNameServers.dialogs.confirmDelete.innerHTML,
				confirmButtonText  = kpdnsNameServers.dialogs.confirmDelete.confirmButtonText,
				cancelButtonText   = kpdnsNameServers.dialogs.confirmDelete.cancelButtonText;

			// Replace NS name (domain) wildcard.
			innerHTML         = innerHTML.replace( /%s/g, NSName );
			confirmButtonText = confirmButtonText.replace( '%s', NSName );

			var dialog = '';
			dialog += '<div id="' + id + '" class="kpdns-dialog" title="' + title + '" style="display: none">';
			dialog += innerHTML;
			dialog += '</div>';
			$(dialog).appendTo('body');

			var dialogContent = $( '#' + id );
			dialogContent.dialog( {
				modal: true,
				dialogClass: 'no-close',
				buttons: [
					{
						text: confirmButtonText,
						class: 'button button-primary',
						click: function() {
							window.location.replace(url);
						}
					},
					{
						text: cancelButtonText,
						class: 'button button-secondary kpdns-dialog-cancel-btn',
						click: function() {
							$( this ).dialog( 'close' );
						}
					}
				]
			} );
		}

		// TODO Build a function to render all dialogs.
		function kpdnsRenderGlueRecordsDialog() {
			var title              = kpdnsNameServers.dialogs.nsCreated.title,
				id                 = kpdnsNameServers.dialogs.nsCreated.id,
				innerHTML          = kpdnsNameServers.dialogs.nsCreated.innerHTML,
				confirmButtonText  = kpdnsNameServers.dialogs.nsCreated.confirmButtonText;

			var dialog = '';
			dialog += '<div id="' + id + '" class="kpdns-dialog" title="' + title + '" style="display: none">';
			dialog += innerHTML;
			dialog += '</div>';
			$(dialog).appendTo('body');

			var dialogContent = $( '#' + id );
			dialogContent.dialog( {
				modal: true,
				dialogClass: 'no-close',
				buttons: [
					{
						text: confirmButtonText,
						class: 'button button-primary',
						click: function() {
							$( this ).dialog( 'close' );
						}
					}
				]
			} );
		}

	} );

})(jQuery);