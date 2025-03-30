<?php
// default admin menu icon appearance
class monochrome_Admin_Icons {
	var $version = '1.1.3';
	function __construct(){
		if ( WP_DEBUG || BSM_DEVELOPMENT ) { $this->version .= '-dev-'.time(); }
		add_action( 'admin_head',   array($this, 'filter_css') );
		add_action( 'admin_footer', array($this, 'filter_svg') );
		wp_enqueue_script('monochrome-admin-icons', plugin_dir_url( __FILE__ ).'js/monochrome.js', array('jquery'), $this->version, false);
	}
	function filter_css() {
		?><style>
			#adminmenu > li.wp-not-current-submenu img { filter: url(#grayscale); ?>); /* Firefox 3.5+ */ filter: gray; /* IE6-9 */ -webkit-filter: grayscale(1); /* Google Chrome & Webkit Nightlies */ -moz-opacity:.8; filter:alpha(opacity=80); opacity:.80;}
			#adminmenu > li.wp-not-current-submenu:hover img { filter: none; -webkit-filter: grayscale(0); }
		</style><?php
	}
	function filter_svg(){
		?>
		<svg height="0" xmlns="http://www.w3.org/2000/svg"><filter id="grayscale"><feColorMatrix values="0.3333 0.3333 0.3333 0 0 0.3333 0.3333 0.3333 0 0 0.3333 0.3333 0.3333 0 0 0      0      0      1 0" /></filter></svg>
		<?php
	}
}
add_action('admin_init', create_function('', 'new monochrome_Admin_Icons();') );
?>