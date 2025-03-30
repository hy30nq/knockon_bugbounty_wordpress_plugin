<?php

class MercadoLivre{
	
	public $as_categ_id;
	public $as_word;
	public $as_price_max;	
	public $as_price_min;
	public $as_filtro_id;
	public $as_cust_id;
	public $as_nickname;
	public $as_pcia_id;
	public $as_filter_id;
	public $as_auct_type_id;
	public $as_other_filter_id;
	public $as_order_id = 'DESTACADOS';
	public $user = '';
	public $pwd = '';
	public $as_desde = 0;
	public $as_display_type = 'G';
	public $as_pr_categ_id = 'AD';
	public $as_qshow = 5;
	public $noQCat = 'N';
	public $as_search_both = 'N';
	
	public $tool_id = '5600606';
	
	public $preco_min;
	public $preco_max;
	public $vezes;
	public $frase;
	public $taxa;
	
	
	function __construct(){
		$url = 'http://www.mercadolivre.com.br/org-img/MLB/pms/megashopping/home.xml';
		$json = $this->get_xml_yql($url, 'response.mpago');

		$this->preco_min = (float)$json->query->results->mpago->precoMin;
		$this->preco_max = (float)$json->query->results->mpago->precoMax;
		$this->vezes = (int)$json->query->results->mpago->vezes;
		$this->frase = $json->query->results->mpago->frase;
		$this->taxa = (float)$json->query->results->mpago->taxa;
	}
	
	function get_category($categId){
		
		$url = 'http://www.mercadolivre.com.br/jm/categsXml?as_site_id=MLB&as_categ_id='.$categId;
		$json = $this->get_xml_yql($url, 'response');
		
		return $json->query->results;
	}
	
	function get_html_yql($link, $xpath){
		
		$yql_base_url = "http://query.yahooapis.com/v1/public/yql";
			
		$yql_query = "select * from html where url='".$link."' and xpath='".$xpath."'";
		$yql_query_url = $yql_base_url . "?q=" . urlencode($yql_query);
		$yql_query_url .= "&format=json";
		
		$json = utf8_encode($this->get_by_curl($yql_query_url));
		
		return json_decode($json);
	} 
	
	function get_xml_yql($link, $item_path){
		
		$yql_base_url = "http://query.yahooapis.com/v1/public/yql";
		
		//echo $link;
			
		$yql_query = "select * from xml where url='".$link."' and itemPath='".$item_path."'";
		$yql_query_url = $yql_base_url . "?q=" . urlencode($yql_query);
		$yql_query_url .= "&format=json";  
		
		//echo $yql_query_url;
		
		$json = utf8_encode($this->get_by_curl($yql_query_url));
		
		return json_decode($json);
	}
	
	public function produtos(){
		$json = $this->get_xml_yql($this->url_produtos(), 'response.listing.items.item');
		return $json->query->results->item;
	}
	
	private function url_produtos(){
		
		$xmlUrl = array();
		
		if ($this->as_word != '')
			$xmlUrl[] = 'as_word='.urlencode($this->as_word);
		if ($this->as_categ_id != '')
			$xmlUrl[] = 'as_categ_id='.$this->as_categ_id; 
		if ($this->as_pcia_id != '')
			$xmlUrl[] = 'as_pcia_id='.$this->as_pcia_id;
		if ($this->as_other_filter_id != '')
			$xmlUrl[] = 'as_other_filter_id='.$this->as_other_filter_id;
		if ($this->as_price_min != '')
			$xmlUrl[] = 'as_price_min='.$this->as_price_min;
		if ($this->as_price_max != '')
			$xmlUrl[] = 'as_price_max='.$this->as_price_max;
		if ($this->as_filter_id != '')
			$xmlUrl[] = 'as_filter_id='.$this->as_filter_id;
		if ($this->as_filtro_id != '')
			$xmlUrl[] = 'as_filtro_id='.$this->as_filtro_id;
		if ($this->as_auct_type_id != '')
			$xmlUrl[] = 'as_auct_type_id='.$this->as_auct_type_id;
		$xmlUrl[] = 'as_qshow='.$this->as_qshow;
		$xmlUrl[] = 'noQCat='.$this->noQCat;
		$xmlUrl[] = 'as_search_both='.$this->as_search_both;
		$xmlUrl[] = 'as_pr_categ_id='.$this->as_pr_categ_id;
		$xmlUrl[] = 'as_display_type='.$this->as_display_type;
		if ($this->as_desde != '')
			$xmlUrl[] = 'as_desde='.$this->as_desde;
		$xmlUrl[] = 'user='.$this->user; 
		$xmlUrl[] = 'pwd='.$this->pwd;  
		if ($this->as_order_id != '')
			$xmlUrl[] = 'as_order_id='.$this->as_order_id; 
		
		//$this->tags();
		
		return 'http://xml.mercadolivre.com.br/jm/searchXml?'.implode('&', $xmlUrl);
	}
		
	function get_by_curl($url){
		$ch = curl_init ();
		curl_setopt ( $ch, CURLOPT_URL, $url );
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt ( $ch, CURLOPT_ENCODING, 'UTF-8');
		curl_setopt ( $ch, CURLOPT_CONNECTTIMEOUT, 0 );
		
		$string = utf8_encode(curl_exec ( $ch ));
		
		curl_close ( $ch );
		
		return $string;
	}
}

?>