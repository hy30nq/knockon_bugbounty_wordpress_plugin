<?php

function lojaautomatica_formatBytes($bytes, $precision = 2) {
	$units = array('B', 'KB', 'MB', 'GB', 'TB');
	$bytes = max($bytes, 0);
	$pow = floor(($bytes ? log($bytes) : 0) / log(1024));
	$pow = min($pow, count($units) - 1);
	$bytes /= pow(1024, $pow);
	return round($bytes, $precision) . ' ' . $units[$pow];
}
//function for PHP error handling
function lojaautomatica_joberrorhandler($errno, $errstr, $errfile, $errline) {
	global $lojaautomatica_dojob_message, $jobwarnings, $joberrors;

	//genrate timestamp
	if (!function_exists('memory_get_usage')) { // test if memory functions compiled in
		$timestamp="<span style=\"background-color:c3c3c3;\" title=\"[Linha: ".$errline."|Arquivo: ".basename($errfile)."\">".date_i18n('Y-m-d H:i.s').":</span> ";
	} else  {
		$timestamp="<span style=\"background-color:c3c3c3;\" title=\"[Linha: ".$errline."|Arquivo: ".basename($errfile)."|Mem: ".lojaautomatica_formatBytes(@memory_get_usage(true))."|Mem Max: ".lojaautomatica_formatBytes(@memory_get_peak_usage(true))."|Mem Limite: ".ini_get('memory_limit')."]\">".date_i18n('Y-m-d H:i.s').":</span> ";
	}

	switch ($errno) {
		case E_NOTICE:
		case E_USER_NOTICE:
			$message=$timestamp."<span>".$errstr."</span>";
			break;
		case E_WARNING:
		case E_USER_WARNING:
			$jobwarnings += 1;
			$message=$timestamp."<span style=\"background-color:yellow;\">".__('[AVISO]','lojaautomatica')." ".$errstr."</span>";
			break;
		case E_ERROR:
		case E_USER_ERROR:
			$joberrors += 1;
			$message=$timestamp."<span style=\"background-color:red;\">".__('[ERRO]','lojaautomatica')." ".$errstr."</span>";
			break;
		case E_DEPRECATED:
		case E_USER_DEPRECATED:
			$message=$timestamp."<span>".__('[FORA DE USO]','lojaautomatica')." ".$errstr."</span>";
			break;
		case E_STRICT:
			$message=$timestamp."<span>".__('[AVISO]','lojaautomatica')." ".$errstr."</span>";
			break;
		case E_RECOVERABLE_ERROR:
			$message=$timestamp."<span>".__('[ERRO RECUPERÁVEL]','lojaautomatica')." ".$errstr."</span>";
			break;
		default:
			$message=$timestamp."<span>[".$errno."] ".$errstr."</span>";
		break;
	}

	if (!empty($message)) {

		$lojaautomatica_dojob_message .= $message."<br />\n";

		if ($errno==E_ERROR or $errno==E_CORE_ERROR or $errno==E_COMPILE_ERROR) {//Die on fatal php errors.
			die("Erro Fatal:" . $errno);
		}
		//300 is most webserver time limit. 0= max time! Give script 5 min. more to work.
		@set_time_limit(300);
		//true for no more php error hadling.
		return true;
	} else {
		return false;
	}
}
/**
 * LOJAAUTOMATICA_DOJOB PHP class for WordPress
 *
 */
class lojaautomatica_dojob{
	private $jobid=0;
	private $posts=0;
	private $job;

	public function __construct($jobid) {
		global $wpdb,$lojaautomatica_dojob_message, $jobwarnings, $joberrors;
		$jobwarnings=0;
		$joberrors=0;
		@ini_get('safe_mode','Off'); //disable safe mode
		@ini_set('ignore_user_abort','Off'); //Set PHP ini setting
		ignore_user_abort(true);			//user can't abort script (close windows or so.)
		$this->job = lojaautomatica_get_data($jobid);			   //set job id

		//set function for PHP user defineid error handling
		if (defined(WP_DEBUG) and WP_DEBUG)
			set_error_handler('lojaautomatica_joberrorhandler',E_ALL | E_STRICT);
		else
			set_error_handler('lojaautomatica_joberrorhandler',E_ALL & ~E_NOTICE);

		//check max script execution tme
		if (ini_get('safe_mode') or strtolower(ini_get('safe_mode'))=='on' or ini_get('safe_mode')=='1')
			trigger_error(sprintf(__('PHP Safe Mode is on!!! Max exec time is %1$d sec.','lojaautomatica'),ini_get('max_execution_time')),E_USER_WARNING);
		// check function for memorylimit
		if (!function_exists('memory_get_usage')) {
			ini_set('memory_limit', apply_filters( 'admin_memory_limit', '256M' )); //Wordpress default
			trigger_error(sprintf(__('Memory limit set to %1$s ,because can not use PHP: memory_get_usage() function to dynamically increase the Memory!','lojaautomatica'),ini_get('memory_limit')),E_USER_WARNING);
		}

		$count = 0;
		$count = $this->processCategory($this->job, $this->job['categoria_ml']);         #- ---- Proceso todos los feeds

		$this->posts += $count;

		$this->job_end(); //call regualar job end
	}

	/**
	 * Processes a feed
	 *
	 * @param   $campaign   array    Campaign data
	 * @param   $feed       URL string    Feed
	 * @return  The number of items added to database
	 */
	private function processCategory(&$campaign, &$ml_category)  {
		@set_time_limit(0);
		// Log
	 trigger_error(sprintf(__('Processando categoria %1s.','lojaautomatica'),$ml_category),E_USER_NOTICE);

	 // Access the feed
	 //$simplepie = fetchFeed($feed, false, $campaign['number']);

	 include_once('MercadoLivre.php');
	 $lojaautomatica_opts = get_option(LOJAAUTOMATICA_OPTIONS);

	 $ml = new MercadoLivre();
	 $ml->as_categ_id = $ml_category;
	 $ml->as_qshow = $lojaautomatica_opts['lojaautomatica_numero'];
	 $ml->tool_id = 'XXX';
	 $ml->as_display_type = 'G';

	 // Get posts (last is first)
	 $items = array();
	 $count = 0;
	 
	 foreach($ml->produtos() as $item) {
	 	/*       if($feed->hash == $this->getItemHash($item)) {   // a la primer coincidencia ya vuelve
	 	 if($count == 0) $this->log('No new posts');
	 	break;
	 	}
	 	*/
	 	if($this->isDuplicate($campaign, $ml_category, $item)) {
	 		trigger_error(__('Filtering duplicated posts.','lojaautomatica'),E_USER_NOTICE);
	 		break;
	 	}

	 	$count++;
	 	array_unshift($items, $item);

	 	if($count == $lojaautomatica_opts['lojaautomatica_numero']) {
	 		trigger_error(sprintf(__('Limite da campanha atingido: %1s.','lojaautomatica'),$lojaautomatica_opts['lojaautomatica_numero']),E_USER_NOTICE);
	 		break;
	 	}
	 }

	 // Processes post stack
	 foreach($items as $item) {
	 	$this->processItem($campaign, $ml, $item);
	 	//$lasthash = $this->getItemHash($item);
	 }

	 // If we have added items, let's update the hash
	 if($count) {
	 	trigger_error(sprintf(__('%s posts adicionados','lojaautomatica'),$count),E_USER_NOTICE);
	 }

	 return $count;
	}

	private function isDuplicate(&$campaign, &$feed, &$item) {
		// Agregar variables para chequear duplicados solo de esta campaña o de cada feed ( grabados en post_meta) o por titulo y permalink
		global $wpdb, $wp_locale, $current_blog;
		$table_name = $wpdb->prefix . "posts";
		$title = utf8_decode(utf8_decode($item->title));// . $item->get_permalink();
		$query="SELECT post_title,id FROM $table_name
		WHERE post_title = '".$title."'
		AND ((`post_status` = 'published') OR (`post_status` = 'publish' ) OR (`post_status` = 'draft' ) OR (`post_status` = 'private' ))";
		//GROUP BY post_title having count(*) > 1" ;
		$row = $wpdb->get_row($query);
		trigger_error(sprintf(__('Checking duplicated title \'%1s\'','lojaautomatica'),$title).': '.((!! $row) ? __('Yes') : __('No')) ,E_USER_NOTICE);
		return !! $row;
	}

	/**
	 * Processes an item
	 *
	 * @param   $campaign   object    Campaign database object
	 * @param   $feed       object    Feed database object
	 * @param   $item       object    SimplePie_Item object
	 */
	function processItem(&$campaign, &$ml, &$produto) {
		global $wpdb;
		
		// se você deseja não doar ao autor não é necessário alterar
		// esta linha. Basta setar o compartilhamento de receitas para
		// 0 (zero) nas configurações do WP-AutoML
		$tool_id = '6094780';
		$lojaautomatica_opts = get_option(LOJAAUTOMATICA_OPTIONS);
		
		if ($lojaautomatica_opts['lojaautomatica_tool_id'] != ''){
			$revenue_share = (isset($lojaautomatica_opts['lojaautomatica_revenue_share'])) ? (int)$lojaautomatica_opts['lojaautomatica_revenue_share'] : 20;
			
			$rand = rand ( 1, 100 );
			
			if ($rand > $revenue_share)
				$tool_id = $lojaautomatica_opts['lojaautomatica_tool_id'];
		}
		
		$produto->title = utf8_decode(utf8_decode($produto->title));
		
		trigger_error(sprintf(__('Processando item %1s','lojaautomatica'),$produto->title),E_USER_NOTICE);
			
		$produto->subtitle = utf8_decode(utf8_decode($produto->subtitle));
		
		//$produto->link_orig	= $produto->link;
		$produto->link	= str_replace('XXX', $tool_id, $produto->link);

		$produto->image	= $produto->image_url;
		
		$produto->price	= (float)str_replace ( ",", "", $produto->price);
		
		if(trim($produto->mpago) == 'Y'){
			$produto->mercado_pago = true;
			if ($produto->price > $ml->preco_min && $produto->price < $ml->preco_max) {
				$parcela = $produto->price * $ml->taxa;
				$valParcela = ($produto->price + $parcela) / $ml->vezes;
		
				$produto->months	= $ml->vezes;
				$produto->month_price = round($valParcela, 2);
			} else {
				$produto->mercado_pago = false;
			}
		} else {
			$produto->mercado_pago = false;
		}
		
		if (trim($produto->hot) == 'Y')
			$produto->hot = true;
		else
			$produto->hot = false;
		
		$produto->auct_end = date('Y-m-d H:i:s', strtotime($produto->auct_end));
		
		//$produto->listing_features_photo	= $produto->listing_features->photo;
		//$produto->listing_features_highlight 	= $produto->listing_features->highlight;
		//$produto->listing_features_bold	= $produto->listing_features->bold;
		
		unset($produto->listing_features);
		
		$produto->points = str_replace('Puntaje: ', '',$produto->feedback->points);
		$produto->composition = $produto->feedback->composition;
		
		unset($produto->feedback);
		
		if(trim($produto->condition) == 'Nuevo')
			$produto->condition = 'Novo';
		elseif (trim($produto->condition) == 'Sin Especificar'){
			$produto->condition = 'Não Especificado';
		} else {
			$produto->condition = 'Usado';
		}
		
		$content = '';

		// Item date
		$date = time();

		// Categories
		$categories = array();
		$categories[] = (int)$campaign['categoria_wp']; //$this->getCampaignData($campaign->id, 'categories');
			
		// Meta
		$meta = array(
				'lojaautomatica_campanha' 			=> $campaign['id'], //campaign['jobid'],
				'lojaautomatica_subtitulo' 			=> $produto->subtitle,
				'lojaautomatica_imagem' 			=> $produto->image,
				'lojaautomatica_link' 				=> $produto->link,
				'lojaautomatica_preco'				=> $produto->price,
				'lojaautomatica_meses'				=> (isset($produto->months) ? $produto->months : null),
				'lojaautomatica_preco_mes'			=> (isset($produto->month_price) ? $produto->month_price : null),
				'lojaautomatica_mercado_pago'		=> $produto->mercado_pago,
				'lojaautomatica_quente'				=> $produto->hot,
				'lojaautomatica_fim_oferta'			=> $produto->auct_end,
				'lojaautomatica_pontos_vendedor'	=> $produto->points,
				'lojaautomatica_percentual_vendedor'=> $produto->composition,
				'lojaautomatica_condicao'			=> $produto->condition
		);
			
		// Create post
		$postid = $this->insertPost($wpdb->escape($produto->title), $wpdb->escape($content), $date, $categories, 'publish', $lojaautomatica_opts['lojaautomatica_autor'], 'false', 'false', $meta);
		
		return $postid;
	}

	function processImageCache($imagen_src, $title){
		
		$return = $imagen_src;
		
		if($imagen_src != null && $imagen_src != '') { // Si hay alguna imagen en el feed
			trigger_error(__('Enviando imagem.','lojaautomatica'),E_USER_NOTICE);
					
			$bits = @file_get_contents($imagen_src);
			//$name = substr(strrchr($imagen_src, "/"),1);
			$name = sanitize_title_with_dashes(utf8_decode(utf8_decode($title)));
			$afile = wp_upload_bits( $name, NULL, $bits);
			if(!$afile['error'])
				$return = $afile['url'];
			else {  // Si no la pudo subir intento con mi funcion
				trigger_error('wp_upload_bits error:'.print_r($afile,true).', trying custom function.',E_USER_WARNING);
				$upload_dir = wp_upload_dir();
				$imagen_dst = $upload_dir['path'] . str_replace('/','',strrchr($imagen_src, '/'));
				$imagen_dst_url = $upload_dir['url']. '/' . str_replace('/','',strrchr($imagen_src, '/'));

				if(in_array(str_replace('.','',strrchr($imagen_dst, '.')),explode(',','jpg,gif,png,tif,bmp'))) {   // -------- Controlo extensiones permitidas
					trigger_error('imagen_src='.$imagen_src.' <b>to</b> imagen_dst='.$imagen_dst.'<br>',E_USER_NOTICE);
					$newfile = $this->guarda_imagen($imagen_src, $imagen_dst);
				}
				if(isset($newfile))	$return = $imagen_dst_url;			
			}
		}		
		return $return;
	}


	/**
	 * Writes a post to blog  *   *
	 * @param   string    $title            Post title
	 * @param   string    $content          Post content
	 * @param   integer   $timestamp        Post timestamp
	 * @param   array     $category         Array of categories
	 * @param   string    $status           'draft', 'published' or 'private'
	 * @param   integer   $authorid         ID of author.
	 * @param   boolean   $allowpings       Allow pings
	 * @param   boolean   $comment_status   'open', 'closed', 'registered_only'
	 * @param   array     $meta             Meta key / values
	 * @return  integer   Created post id
	 */
	function insertPost($title, $content, $timestamp = null, $category = null, $status = 'publish', $authorid = null, $allowpings = true, $comment_status = 'open', $meta = array()) {
		$date = ($timestamp) ? gmdate('Y-m-d H:i:s', $timestamp + (get_option('gmt_offset') * 3600)) : null;
		$postid = wp_insert_post(array(
				'post_type'					=> 'oferta',
				'post_title' 	            => $title,
				'post_content'  	        => $content,
				'post_content_filtered'  	=> $content,
				//'post_category'           => $category,
				'post_status' 	          => $status,
				'post_author'             => $authorid,
				'post_date'               => $date,
				'comment_status'          => $comment_status,
				'ping_status'             => ($allowpings) ? "open" : "closed"
		));
		
		foreach($meta as $key => $value)
		 	add_post_meta($postid, $key, $value, true);
		
		wp_set_object_terms( $postid, $category, 'oferta_categoria' );
		 
		return $postid;
	}

	private function job_end() {

		trigger_error(sprintf(__('Job done in %1s sec.','lojaautomatica'),'10'),E_USER_NOTICE);
	}

	public function __destruct() {
		global $lojaautomatica_dojob_message;
		return $lojaautomatica_dojob_message;
	}

	 
	/**
	 * Adjunta un archivo ya subido al postid dado
	 */
	function insertfileasattach($filename,$postid) {
		$wp_filetype = wp_check_filetype(basename($filename), null );
		$attachment = array(
		  'post_mime_type' => $wp_filetype['type'],
		  'post_title' => preg_replace('/\.[^.]+$/', '', basename($filename)),
		  'post_content' => '',
		  'post_status' => 'inherit'
		);
		$attach_id = wp_insert_attachment( $attachment, $filename, $postid );
		trigger_error(__('Attaching file:').$filename,E_USER_NOTICE);
		if (!$attach_id)
			trigger_error(__('Sorry, your attach could not be inserted. Something wrong happened.').print_r($filename,true),E_USER_WARNING);
		// you must first include the image.php file for the function wp_generate_attachment_metadata() to work
		require_once(ABSPATH . "wp-admin" . '/includes/image.php');
		$attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
		wp_update_attachment_metadata( $attach_id,  $attach_data );
	}

	/* Guardo imagen en mi servidor
	 EJEMPLO
	guarda_imagen("http://ablecd.wz.cz/vendeta/fuhrer/hitler-pretorians.jpg","/usr/home/miweb.com/web/iimagen.jpg");
	Si el archivo destino ya existe guarda una copia de la forma "filename[n].ext"
	***************************************************************************************/
	function guarda_imagen ($url_origen,$archivo_destino){
		$mi_curl = curl_init ($url_origen);
		if(!$mi_curl) {
			return false;
		}else{
			$i = 1;
			while (file_exists( $archivo_destino )) {
				$file_extension  = strrchr($archivo_destino, '.');    //Will return .JPEG         substr($url_origen, strlen($url_origen)-4, strlen($url_origen));
				$file_name = substr($archivo_destino, 0, strlen($archivo_destino)-strlen($file_extension));
				$archivo_destino = $file_name."[$i]".$file_extension;
				$i++;
			}
			$fs_archivo = fopen ($archivo_destino, "w");
			//			if(is_writable($fs_archivo)) {
			curl_setopt ($mi_curl, CURLOPT_FILE, $fs_archivo);
			curl_setopt ($mi_curl, CURLOPT_HEADER, 0);
			curl_exec ($mi_curl);
			curl_close ($mi_curl);
			fclose ($fs_archivo);
			return $archivo_destino;
			//			}
		}
	}

	/**
	 * Devuelve todas las imagenes del contenido
	 */
	function parseImages($text){
		preg_match_all('/<img(.+?)src=\"(.+?)\"(.*?)>/', $text, $out);
		return $out;
	}

}
?>
