( function ( $, wp ) {
	'use strict';

	if ( ! wp || ! wp.media ) {
		return;
	}

	function createItem( image ) {
		var item = $( '<li class="tharchive-gallery-picker__item" />' );
		var thumb = $( '<div class="tharchive-gallery-picker__thumb" />' );
		var meta = $( '<div class="tharchive-gallery-picker__meta" />' );
		var removeButton = $( '<button type="button" class="button-link-delete" data-tharchive-gallery-remove="1">移除</button>' );
		var thumbnailUrl = image.thumbnailUrl || image.url || '';
		var fullUrl = image.url || thumbnailUrl;
		var title = image.title || '未命名图片';

		item.attr( 'data-attachment-id', image.id );
		item.attr( 'data-thumbnail-url', thumbnailUrl );
		item.attr( 'data-full-url', fullUrl );
		item.attr( 'data-title', title );

		thumb.append( $( '<img alt="" />' ).attr( 'src', thumbnailUrl ) );
		meta.append( $( '<strong />' ).text( title ) );
		meta.append( $( '<span />' ).text( 'ID: ' + image.id ) );

		item.append( thumb );
		item.append( meta );
		item.append( removeButton );

		return item;
	}

	function collectIds( $list ) {
		return $list
			.children( '[data-attachment-id]' )
			.map( function () {
				return String( $( this ).data( 'attachment-id' ) || '' );
			} )
			.get()
			.filter( function ( id ) {
				return id !== '';
			} );
	}

	function syncState( $picker ) {
		var $list = $picker.find( '[data-tharchive-gallery-list="1"]' );
		var ids = collectIds( $list );

		$picker.find( '[data-tharchive-gallery-input="1"]' ).val( ids.join( ',' ) );
		$picker.find( '[data-tharchive-gallery-empty="1"]' ).toggleClass( 'hidden', ids.length > 0 );
		$picker.find( '[data-tharchive-gallery-clear="1"]' ).toggleClass( 'hidden', ids.length === 0 );
	}

	$( function () {
		$( '[data-tharchive-gallery-picker="1"]' ).each( function () {
			var $picker = $( this );
			var $list = $picker.find( '[data-tharchive-gallery-list="1"]' );
			var frame = null;

			syncState( $picker );

			$picker.on( 'click', '[data-tharchive-gallery-open="1"]', function ( event ) {
				event.preventDefault();

				if ( frame ) {
					frame.open();
					return;
				}

				frame = wp.media( {
					title: window.tharchiveEventAdminMedia && window.tharchiveEventAdminMedia.frameTitle ? window.tharchiveEventAdminMedia.frameTitle : '选择活动图集',
					button: {
						text: window.tharchiveEventAdminMedia && window.tharchiveEventAdminMedia.frameButton ? window.tharchiveEventAdminMedia.frameButton : '使用这些图片'
					},
					multiple: true,
					library: {
						type: 'image'
					}
				} );

				frame.on( 'open', function () {
					var selection = frame.state().get( 'selection' );

					$list.children( '[data-attachment-id]' ).each( function () {
						var attachmentId = $( this ).data( 'attachment-id' );
						var attachment = wp.media.attachment( attachmentId );

						attachment.fetch();
						selection.add( attachment ? [ attachment ] : [] );
					} );
				} );

				frame.on( 'select', function () {
					var selection = frame.state().get( 'selection' );

					$list.empty();

					selection.each( function ( attachment ) {
						var data = attachment.toJSON();
						var thumb = data.sizes && data.sizes.thumbnail ? data.sizes.thumbnail.url : data.icon;
						var image = {
							id: data.id,
							title: data.title,
							url: data.url,
							thumbnailUrl: thumb || data.url
						};

						$list.append( createItem( image ) );
					} );

					syncState( $picker );
				} );

				frame.open();
			} );

			$picker.on( 'click', '[data-tharchive-gallery-remove="1"]', function ( event ) {
				event.preventDefault();
				$( this ).closest( '[data-attachment-id]' ).remove();
				syncState( $picker );
			} );

			$picker.on( 'click', '[data-tharchive-gallery-clear="1"]', function ( event ) {
				event.preventDefault();
				$list.empty();
				syncState( $picker );
			} );
		} );
	} );
}( jQuery, window.wp ) );
