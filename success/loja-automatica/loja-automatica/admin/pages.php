<?php
function lojaautomatica_configuration_settings(){
  global $lojaautomatica;
  
  add_meta_box(
  		'lojaautomatica-general-options-meta-box'
  		,__( 'Opções Gerais', 'lojaautomatica' )
  		,'lojaautomatica_general_options_meta_box'
  		,$lojaautomatica->settings
  		,'cron'
  		,'high'
  );
  
  add_meta_box( 
             'lojaautomatica-cron-meta-box'
            ,__( 'Unix Cron', 'lojaautomatica' )
            ,'lojaautomatica_cron_meta_box'
            ,$lojaautomatica->settings
            ,'cron'
            ,'high'
        ); 

  add_meta_box( 
             'lojaautomatica-revenueshare-meta-box'
            ,__( 'Compartilhamento de Receitas', 'lojaautomatica' )
            ,'lojaautomatica_revenueshare_meta_box'
            ,$lojaautomatica->settings
            ,'cron'
            ,'high'
        ); 
}

function lojaautomatica_add_new_settings(){
   global $lojaautomatica;
  
  add_meta_box( 
             'lojaautomatica-category-meta-box'
            ,__( 'Categorias de Post', 'lojaautomatica' )
            ,'lojaautomatica_campagins_category_meta_box'
            ,$lojaautomatica->addnew
            ,'category'
            ,'high'
        );
   add_meta_box( 
             'lojaautomatica-category-new-meta-box'
            ,__( 'Adicionar Nova Categoria', 'lojaautomatica' )
            ,'lojaautomatica_campagins_new_category_meta_box'
            ,$lojaautomatica->addnew
            ,'new_category'
            ,'high'
        );        
   add_meta_box( 
             'lojaautomatica-number-meta-box'
            ,__( 'Número de Ofertas', 'lojaautomatica' )
            ,'lojaautomatica_campagins_number_meta_box'
            ,$lojaautomatica->addnew
            ,'number'
            ,'high'
        );        
  add_meta_box( 
             'lojaautomatica-content-meta-box'
            ,__( 'Opções', 'lojaautomatica' )
            ,'lojaautomatica_campaigns_ml_meta_box'
            ,$lojaautomatica->addnew
            ,'mercadolivre'
            ,'high'
        ); 
}

function lojaautomatica_campaigns_page(){
   //Create an instance of our package class...
    $campaignsListTable = new Campaigns_List_Table();
    //Fetch, prepare, sort, and filter our data...
    $campaignsListTable->prepare_items();
?>
 <div class="wrap">
        
        <?php if ( function_exists( 'screen_icon' ) ) screen_icon(); ?>
        <h2><?php _e( 'Lista de Categorias para Importação', 'lojaautomatica' ); ?></h2>
        <?php if ( isset( $_GET['success'] ) && 'true' == esc_attr( $_GET['success'] ) ) lojaautomatica_success_message(); ?>
        <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
        
        <h3>Estas são as categorias ativas para importação de ofertas do MercadoLivre. Para adicionar categorias utilize o menu "Criar Categorias"</h3>
        
        <form id="campaigns-filter" method="get">
            <!-- For plugins, we also need to ensure that the form posts back to our current page -->
            <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
            <!-- Now we can render the completed list table -->
            <?php $campaignsListTable->display() ?>
        </form>
        
    </div>
    <?php   
}

function lojaautomatica_add_new_page(){
   global $lojaautomatica;
   $plugin_data = get_plugin_data( LOJAAUTOMATICA_DIR . 'loja-automatica.php' );
   if(!empty($_GET['id'])){
     global $wpdb;
     $lojaautomatica_table = $wpdb->prefix.'lojaautomatica';
     $query = "SELECT * FROM $lojaautomatica_table WHERE id = ".$_GET['id'];
     $data = $wpdb->get_row($query);
     //$content = $data->content;     
     $content = '';
   }else{
	 $content = '';   
   }
?>
   <div class="wrap">
		
        <?php if ( function_exists( 'screen_icon' ) ) screen_icon(); ?>
        
		<h2><?php _e( 'Adicionar Categorias', 'lojaautomatica' ); ?></h2>
        <?php if ( isset( $_GET['updated'] ) && 'true' == esc_attr( $_GET['updated'] ) && !empty($_GET['id']) ) lojaautomatica_update_message(); ?>
       	
       	<div id="message" class="updated fade">
			<ul><li> Adicione as categorias do Mercado Livre de onde deseja importar as ofertas. Após clicar em Salvar, seja paciente, a criação de categorias pode levar mais de dois minutos conforme o número de categorias.</li></ul>
		</div>
       		     
        <form id="addnew" method="post">
		<input name="action" value="<?php if(!empty($_GET['id'])){echo "update";}else{echo "create";}?>" type="hidden">
		<div id="poststuff" class="metabox-holder">	
				<div id="post-body">
				   <div id="post-body-content">
				      <?php do_meta_boxes( $lojaautomatica->addnew, 'mercadolivre', $plugin_data ); ?>
				   </div><!-- #post-body-content -->
				</div><!-- post-body -->
		</div><!-- #poststuff -->
		<br class="clear">
        <input class="button button-primary" type="submit" value="<?php _e( 'Criar Categorias', 'lojaautomatica' ); ?>" name="campaign" />
        </form>
	</div><!-- .wrap -->  
<?php       
}

function lojaautomatica_configuration_page(){
global $lojaautomatica;

	$plugin_data = get_plugin_data( LOJAAUTOMATICA_DIR . 'loja-automatica.php' );  ?>

	<div class="wrap">
		
        <?php if ( function_exists( 'screen_icon' ) ) screen_icon(); ?>
        <?php if ( isset( $_GET['updated'] ) && 'true' == esc_attr( $_GET['updated'] ) ) lojaautomatica_update_message(); ?>
		<h2><?php _e( 'Opções Gerais', 'lojaautomatica' ); ?></h2>
       
        <form id="settings" method="post">		
		<div id="poststuff">			               
				<div class="metabox-holder">
					
					<div class="post-box-container column-1 cron"><?php do_meta_boxes( $lojaautomatica->settings, 'cron', $plugin_data ); ?></div>
							            
		       	</div>						
		</div><!-- #poststuff -->
     <br class="clear">
        <input class="button button-primary" type="submit" value="<?php _e('Save'); ?>" name="settings" />
        </form>
	</div><!-- .wrap -->  	
<?php	
}
?>