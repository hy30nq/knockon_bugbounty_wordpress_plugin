jQuery(document).ready(function($){
	$('#adminmenu').monochrome_admin_icons();
});

(function($) {

	$.monochrome_admin_icons = function(menu, options) {

		var defaults = {},
				plugin = this,
				$menu = $(menu),
				menu = menu,
				$submenus, $imgs;

		$submenus = $menu.find('> li:not(.wp-has-current-submenu)');
		$imgs = $submenus.find('img'); // This selector doesn't get default WordPress icons. They're background sprites, not img tags.
		
		plugin.settings = {};

		plugin.init = function() {
			plugin.settings = $.extend({}, defaults, options);

			// Attach to load event of individual images in the Admin Menu
			// Hide to reduce visibility of color icons
			if ( 
				!webkit_filter_supported()
				&& !filter_supported()
				&& canvas_supported() 
			) {
				$imgs.hide().each( process_with_canvas );
				canvas_hover_states();
			}

		}

		var webkit_filter_supported = function() {
			return '-webkit-filter' in document.body.style;
		}

		var filter_supported = function() {
			if ( $.browser.webkit ) {
				return false;
			}else {
				return 'filter' in document.body.style;
			}
		}

		var canvas_supported = function(){
			var elem = document.createElement('canvas');
			return !!(elem.getContext && elem.getContext('2d'));
		}

		var canvas_hover_states = function(){
			$submenus.hover(
				function(){
					var img = $(this).find('img.mono');
					img.attr('src', img.data('color-src'));
				},
				function(){
					var img = $(this).find('img.mono');
					img.attr('src', img.data('mono-src'));
				}
			);
		}

		/**
		 * See http://webdesignerwall.com/tutorials/html5-grayscale-image-hover
		 */
		var process_with_canvas = function(){
			var img = $(this),
				img_tmp = new Image(),
				color_src = img.attr('src');

			// Wait until image data loads to process anything
			$( img_tmp ).bind( 'load', function(){ canvas_onload( img, img_tmp ) } );

			img.data( 'color-src', color_src );
			img_tmp.src = color_src;
		}

		var canvas_onload = function( img, img_tmp ) {
			var canvas = document.createElement('canvas'),
				ctx = canvas.getContext('2d'),
				imgPixels,
				mono_src;

			if ( img_tmp.height > 20 ) {
				// Single icons are 16px tall. This is probably a sprite.
				img.show();
				return;
			}

			canvas.width = img_tmp.width;
			canvas.height = img_tmp.height; 
			ctx.drawImage(img_tmp, 0, 0); // Insert image into canvas
			imgPixels = ctx.getImageData(0, 0, canvas.width, canvas.height); // Load image pixels

			// Calculate monochrome pixel values
			for(var y = 0; y < imgPixels.height; y++){
				for(var x = 0; x < imgPixels.width; x++){
					var i = (y * 4) * imgPixels.width + x * 4;
					var avg = (imgPixels.data[i] + imgPixels.data[i + 1] + imgPixels.data[i + 2]) / 3;
					imgPixels.data[i] = avg; 
					imgPixels.data[i + 1] = avg; 
					imgPixels.data[i + 2] = avg;
				}
			}
			// Replace color pixels with monochrome values
			ctx.putImageData(imgPixels, 0, 0, 0, 0, imgPixels.width, imgPixels.height);
			
			// Convert canvas to base64 encoded URL
			mono_src = canvas.toDataURL();

			// Update the color image with new data
			img.attr('src', mono_src)
				.data('mono-src', mono_src)
				.addClass('mono');

			// Avoid flicker
			setTimeout(function(){ img.show(); }, 200 );
		}

		plugin.init();

	}

	$.fn.monochrome_admin_icons = function(options) {

		return this.each(function() {
				if (undefined == $(this).data('monochrome_admin_icons')) {
					var plugin = new $.monochrome_admin_icons(this, options);
					$(this).data('monochrome_admin_icons', plugin);
				}
		});

	}

})(jQuery);