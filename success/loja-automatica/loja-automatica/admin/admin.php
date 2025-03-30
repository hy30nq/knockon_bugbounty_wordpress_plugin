<?php
/* Admin functions to set and save settings of the
 * @package LOJAAUTOMATICA
*/
require_once('pages.php');
require_once('meta_box.php');
require_once(LOJAAUTOMATICA_INC.'list_table.php');
require_once(LOJAAUTOMATICA_INC.'MercadoLivre.php');
//require_once(LOJAAUTOMATICA_INC.'twitteroauth.php');
//require_once(LOJAAUTOMATICA_INC.'facebook.php');
/* Initialize the theme admin functions */
add_action('init', 'lojaautomatica_admin_init');

function lojaautomatica_admin_init(){
		
	add_action('admin_menu', 'lojaautomatica_settings_init');
	add_action('admin_init', 'lojaautomatica_actions_handler');
	add_action('admin_init', 'lojaautomatica_admin_style');
	add_action('admin_init', 'lojaautomatica_admin_script');

}

function lojaautomatica_settings_init(){
	global $lojaautomatica;
	add_menu_page('Loja Automática', 'Loja Automática', 'import', 'lojaautomatica-campaigns', 'lojaautomatica_campaigns_page' );
	//$lojaautomatica->campaings = add_submenu_page('lojaautomatica-campaigns', 'Categorias', 'Categorias', 'import', 'lojaautomatica-campaigns', 'lojaautomatica_campaigns_page' );
	$lojaautomatica->addnew = add_submenu_page('lojaautomatica-campaigns', 'Criar Categorias', 'Criar Categorias', 'import', 'lojaautomatica-add-new', 'lojaautomatica_add_new_page');
	$lojaautomatica->settings = add_submenu_page('lojaautomatica-campaigns', 'Configurações', 'Configurações', 'import', 'lojaautomatica-settings', 'lojaautomatica_configuration_page' );

	add_action( "load-{$lojaautomatica->addnew}", 'lojaautomatica_add_new_settings');
	add_action( "load-{$lojaautomatica->settings}", 'lojaautomatica_configuration_settings');
}

function lojaautomatica_admin_style(){
	$plugin_data = get_plugin_data( LOJAAUTOMATICA_DIR . 'loja-automatica.php' );
	wp_enqueue_style( 'lojaautomatica-admin', LOJAAUTOMATICA_CSS . 'style.css', false, $plugin_data['Version'], 'screen' );
}

function lojaautomatica_admin_script(){
}

function lojaautomatica_actions_handler(){
	global $wpdb;
	 
	if(isset($_GET['action']) && $_GET['action']=='campaign-run'){
		lojaautomatica_cron_hook($_GET['id']);
		$redirect = admin_url( 'admin.php?page=lojaautomatica-campaigns&success=true' );
		wp_redirect($redirect);
	}

	if(isset($_GET['action']) && $_GET['action']=='campaign-delete'){
		$lojaautomatica_table = $wpdb->prefix.'lojaautomatica';
		$query = "DELETE FROM $lojaautomatica_table WHERE id=".$_GET['id'];
		$wpdb->query($query);
		$redirect = admin_url( 'admin.php?page=lojaautomatica-campaigns' );
		wp_redirect($redirect);
	}

	if(isset($_POST['settings'])){
		$lojaautomatica_opts = get_option(LOJAAUTOMATICA_OPTIONS);
		$lojaautomatica_opts['lojaautomatica_numero'] = $_POST['numero'];
		if ($lojaautomatica_opts['lojaautomatica_schedule'] != $_POST['schedule']){
			$lojaautomatica_opts['lojaautomatica_schedule'] = $_POST['schedule'];
			wp_clear_scheduled_hook('lojaautomatica_cron');
			wp_schedule_event(time(), $_POST['schedule'], 'lojaautomatica_cron');
		}
		$lojaautomatica_opts['lojaautomatica_unix_cron'] = $_POST['unix_cron'];
		$lojaautomatica_opts['lojaautomatica_tool_id'] = $_POST['tool_id'];
		$lojaautomatica_opts['lojaautomatica_revenue_share'] = (int)$_POST['revenue_share'];
		update_option(LOJAAUTOMATICA_OPTIONS, $lojaautomatica_opts);
		wp_redirect(admin_url('admin.php?page=lojaautomatica-settings&updated=true'));
	}
	 
	if(isset($_POST['campaign'])){
		$parent = ($_POST['category'] == -1) ? null : $_POST['category'];
	 	$include_subcats = $_POST['create_subcategory'];
	 	
	 	foreach(explode(',', $_POST['categoria_ml']) as $cat_ml){
	 		if (isset($cat_ml) && $cat_ml != '')
	 			lojaautomatica_create_category($cat_ml, $parent, null, $include_subcats);
	 	}
	 	
	 	$redirect = admin_url( 'admin.php?page=lojaautomatica-add-new&updated=true&id=' ).$wpdb->insert_id;
	 	wp_redirect($redirect);
	}
}

function lojaautomatica_error_message(){
	echo '<div class="error">
	<p>Erro</p>
	</div>';
}
function lojaautomatica_success_message(){
	echo '<div class="updated fade">
	<p>Campanha inserida com sucesso.</p>
	</div>';
}
function lojaautomatica_update_message(){
	echo '<div class="updated fade">
	<p>Configurações Atualizadas</p>
	</div>';
}
function lojaautomatica_create_message(){
	echo '<div class="updated fade">
	<p>Campanha Criada</p>
	</div>';
}

function lojaautomatica_create_category($ml_id, $parent_wp, $parent_ml, $include_subcats = true){
	
	global $wpdb;
	$ml = new MercadoLivre();
	
	$retorno = $ml->get_category($ml_id);
	
	$wp_cat = $wpdb->get_var( "SELECT categoria_wp FROM ".$wpdb->prefix."lojaautomatica WHERE categoria_ml = ".$ml_id );
	
	if( !isset($wp_cat) ){
		$wp_cat = wp_insert_term(utf8_decode(utf8_decode($retorno->response->categories->category->name)), 'oferta_categoria', array('parent'=> $parent_wp));
		$insert = array('categoria_wp' => $wp_cat['term_id'], 'categoria_ml' => $ml_id, 'pai' => $parent_ml, 'nome_categoria_ml' => utf8_decode(utf8_decode($retorno->response->categories->category->name)));
		$wpdb->insert($wpdb->prefix."lojaautomatica", $insert);
	}
	
	if ($include_subcats && isset($retorno->response->categories->category->category)){
		if (is_array($retorno->response->categories->category->category)){
			foreach($retorno->response->categories->category->category as $cat){
				lojaautomatica_create_subcategory($cat, $wp_cat, $ml_id, $include_subcats);
			}
		} else {
			lojaautomatica_create_subcategory($retorno->response->categories->category->category, $wp_cat, $ml_id, $include_subcats);
		}
	}

}

function lojaautomatica_create_subcategory($ml_cat, $parent_wp, $parent_ml, $include_subcats = true){

	global $wpdb;

	$wp_cat = $wpdb->get_var( "SELECT categoria_wp FROM ".$wpdb->prefix."lojaautomatica WHERE categoria_ml = ".$ml_cat->id );

	if( !isset($wp_cat) ){
		$wp_cat = wp_insert_term(utf8_decode(utf8_decode($ml_cat->name)), 'oferta_categoria', array('parent'=> $parent_wp));
		$insert = array('categoria_wp' => $wp_cat['term_id'], 'categoria_ml' => $ml_cat->id, 'pai' => $parent_ml, 'nome_categoria_ml' => utf8_decode(utf8_decode($ml_cat->name)));
		$wpdb->insert($wpdb->prefix."lojaautomatica", $insert);
	}

	if ($include_subcats && isset($ml_cat->category)){
		if (is_array($ml_cat->category)){
			foreach($ml_cat->category as $cat){
				lojaautomatica_create_subcategory($cat, $wp_cat, $ml_cat->id, $include_subcats);
			}
		} else {
			lojaautomatica_create_subcategory($ml_cat->category, $wp_cat, $ml_cat->id, $include_subcats);
		}
	}
}

?>
