<?php

function lojaautomatica_general_options_meta_box(){
   $lojaautomatica_opts = get_option(LOJAAUTOMATICA_OPTIONS);
   $schedule = (isset($lojaautomatica_opts['lojaautomatica_schedule']))?$lojaautomatica_opts['lojaautomatica_schedule']:'30minutes'; 
   $tool_id = (isset($lojaautomatica_opts['lojaautomatica_tool_id']))?$lojaautomatica_opts['lojaautomatica_tool_id']:''; 
   $numero = (isset($lojaautomatica_opts['lojaautomatica_numero']))?$lojaautomatica_opts['lojaautomatica_numero']:'10'; 
   $usuario_xml = (isset($lojaautomatica_opts['lojaautomatica_usuario_xml']))?$lojaautomatica_opts['lojaautomatica_usuario_xml']:''; 
   $senha_xml = (isset($lojaautomatica_opts['lojaautomatica_senha_xml']))?$lojaautomatica_opts['lojaautomatica_senha_xml']:''; 
?>	
<table class="form-table">
	<tbody>
	<tr>
		<th><label>Intervalo entre as importações: </label></th>
		<td><select name="schedule">
				<option value="15minutes" <?php echo ($schedule == '15minutes') ? 'selected="selected"' : '' ?>>15 minutos</option>
				<option value="30minutes" <?php echo ($schedule == '30minutes') ? 'selected="selected"' : '' ?>>30 minutos</option>
				<option value="hourly" <?php echo ($schedule == 'hourly') ? 'selected="selected"' : '' ?>>1 hora</option>
				<option value="2hours" <?php echo ($schedule == '2hours') ? 'selected="selected"' : '' ?>>2 horas</option>
				<option value="twicedaily" <?php echo ($schedule == 'twicedaily') ? 'selected="selected"' : '' ?>>12 horas</option>
				<option value="daily" <?php echo ($schedule == 'daily') ? 'selected="selected"' : '' ?>>24 horas</option>
			</select><!--  (próxima: <?php echo date('d/m/Y H:i:s', wp_next_scheduled('lojaautomatica_cron')) ?>) -->
		</td>
	</tr>
	<tr>
		<th><label>Tool ID do Mercado Livre: </label></th>
		<td><input type="text" size="10" value="<?php echo $tool_id ?>" name="tool_id" /><p class="howto">Se você não tem um Tool ID ou não sabe o que é, acesse o FAQ <a href="http://pmsapp.mercadolivre.com.br/jm/ml.faqs.framework.main.FaqsController?pageId=FAQ&faqId=5910&categId=promo&type=FAQ">"Como criar um Tool ID?"</a>.</p></td>
	</tr>
	<tr>
		<th><label>Número de ofertas por importação: </label></th>
		<td><input type="text" size="3" value="<?php echo $numero ?>" name="numero" /><p class="howto">Número de ofertas adicionadas ao blog por importação. Máximo de 10 ofertas se não estiver usando um usuário e senha XML.</td>
	</tr>
	<tr>
		<th><label>Usuário XML: </label></th>
		<td><input type="text" size="20" value="<?php echo $usuario_xml ?>" name="usuario_xml" /><p class="howto">Não obrigatório, mas permite incluir até 100 ofertas por importação. Para solicitar gratuitamente, utilize o <a href="http://www.mercadolivre.com.br/brasil/ml/org_board.p_main?an_curr_board=15286">"Fórum XML do Mercado Sócios"</a>.</p></td>
	</tr>
	<tr>
		<th><label>Senha XML: </label></th>
		<td><input type="text" size="20" value="<?php echo $senha_xml ?>" name="senha_xml" /><p class="howto">Não obrigatório.</p></td>
	</tr>
	</tbody>
</table>
<?php      
}

function lojaautomatica_cron_meta_box(){
   $lojaautomatica_opts = get_option(LOJAAUTOMATICA_OPTIONS);
   $unix_cron = (isset($lojaautomatica_opts['lojaautomatica_unix_cron']))?$lojaautomatica_opts['lojaautomatica_unix_cron']:false; 
?>	
   <p><input type="checkbox" value="true" name="unix_cron" <?php if($unix_cron == 'true'){echo 'checked="checked"'; }?> />
   <label>Habilitar Cron do UNIX</label></p>
   
   <p class="howto">comando: <i> php -q <?PHP echo ABSPATH.'wp-content/plugins/wp-rss-poster/cron.php'; ?></i></p>   
<?php      
}

function lojaautomatica_revenueshare_meta_box(){
   $lojaautomatica_opts = get_option(LOJAAUTOMATICA_OPTIONS);
   $revenueshare = (isset($lojaautomatica_opts['lojaautomatica_revenue_share']))?$lojaautomatica_opts['lojaautomatica_revenue_share']:'20';
?>	
   <p><input size="3" type="text" value="<?php echo $revenueshare ?>" name="revenue_share" />%
   <label>Percentual de uso do Tool ID do criador do Plugin</label></p>
   
   <p class="howto">Percentual de produtos que levarão o Tool ID do criador do plugin. <b>POR FAVOR</b>, mantenha um percentual razoável (acima de 10%). Desenvolver esse plugin foi bem trabalhoso. A manutenção de atualizações para ele também depende do quanto é rentável. :)</p>   
<?php      
}

function lojaautomatica_campaigns_ml_meta_box(){
   global $lojaautomatica;
   if(!empty($_GET['id'])){
     global $wpdb;
     $lojaautomatica_table = $wpdb->prefix.'lojaautomatica';
     $query = "SELECT * FROM $lojaautomatica_table WHERE id = ".$_GET['id'];
     $data = $wpdb->get_row($query);                    
   }
   $plugin_data = get_plugin_data( LOJAAUTOMATICA_DIR . 'loja-automatica.php' );
   wp_enqueue_script( 'lojaautomatica-admin', LOJAAUTOMATICA_JS . 'custom.js', false, $plugin_data['Version'], true );   
?>
   <table class="form-table">
	 <tr>
	   <th>
        	<label for="category_ml"><?php _e( 'Categorias do Mercado Livre:', 'lojaautomatica' ); ?></label>
	   </th>	 
	   <td>
		    <select id="categorias-1" class="cat1" size="7" style="height:120px" multiple="multiple"></select>
		    <input type="button" id="add-1" class="addcat cat1" value="Adicionar >>" /><br class="cat2"/>
		    <select id="categorias-2" class="cat2" size="7" style="height:120px" multiple="multiple"></select>
		    <input type="button" id="add-2" class="addcat cat2" value="Adicionar >>" /><br class="cat3"/>
		    <select id="categorias-3" class="cat3" size="7" style="height:120px" multiple="multiple"></select>
		    <input type="button" id="add-3" class="addcat cat3" value="Adicionar >>" /><br class="cat4"/>
		    <select id="categorias-4" class="cat4" size="7" style="height:120px" multiple="multiple"></select>
		    <input type="button" id="add-4" class="addcat cat4" value="Adicionar >>" /><br class="cat4"/>
		    
		    <div class="howto">Utilize CTRL ou SHIFT para selecionar mais de uma categoria.</div>
	   </td> 
	 </tr>
	 <tr>
	   <th>
        	<label for="category_ml"><?php _e( 'Categorias Adicionadas:', 'lojaautomatica' ); ?></label>
	   </th>	 
	   <td>
		    <input id="categoria_ml" name="categoria_ml" style="display:none;" readonly="readonly" type="text" value="<?php if(isset($data)){echo $data->category_ml; }?>" />
		    <!-- <input id="categoria_ml_nomes" name="categoria_ml_nomes" readonly="readonly" type="text" value="<?php if(isset($data)){echo $data->category_ml; }?>" /> -->
		    <div id="texto_categorias"></div>
	   </td> 
	 </tr>
	 <tr>
	    <th>
        	<label for="cache">Criar na Categoria:</label> 
	   </th>	 
	   <td>
		    <?php wp_dropdown_categories(array('taxonomy' => 'oferta_categoria', 'show_option_none' => 'Nenhuma', 'hierarchical' => true, 'name' => 'category', 'hide_empty' => 0)) ?>
		    <div class="howto">As categorias do MercadoLivre que você escolheu serão criadas dentro dessa categoria. Se você escolher "Nenhuma", as categorias serão criadas como categorias principais.</div>
	   </td> 
	 </tr>
	 <tr>
	    <th>
        	<label for="cache">Criar subcategorias no Wordpress?</label> 
	   </th>	 
	   <td>
		    <input type="checkbox" value="true" name="create_subcategory" checked="checked" />
		    <div class="howto">Essa opção vai criar também as subcategorias das categorias do ML selecionadas.</div>
	   </td> 
	 </tr>
	   
	</table><!-- .form-table -->
	
<?php	
}
?>
