<?php 
add_action('init', 'create_custom', 0);

function create_custom() {
	
	$args = array(
			'description' => 'Anúncio do Mercado Livre',
			'show_ui' => true,
			'menu_position' => 4,
			'labels' => array(
					'name'=> 'Ofertas',
					'singular_name' => 'Oferta',
					'add_new' => 'Adicionar Nova Oferta',
					'add_new_item' => 'Adicionar Nova Oferta',
					'edit' => 'Editar Ofertas',
					'edit_item' => 'Editar Oferta',
					'new-item' => 'Nova Oferta',
					'view' => 'Ver Ofertas',
					'view_item' => 'Ver Oferta',
					'search_items' => 'Buscar Ofertas',
					'not_found' => 'Nenhuma Oferta Encontrada',
					'not_found_in_trash' => 'Nenhuma Oferta na Lixeira',
					'parent' => 'Oferta Pai'
			),
			'public' => true,
			'publicly_queryable' => true,
			'show_in_menu' => true,
			'capability_type' => 'post',
			'hierarchical' => false,
			'rewrite' => array( 'slug' => 'oferta', 'with_front' => false ),
			'supports' => array('title', 'editor', 'thumbnail', 'custom-fields')
	);
	register_post_type( 'oferta' , $args );
	
	
	register_taxonomy('oferta_categoria',
			'oferta',
			array (
					'labels' => array (
							'name' => 'Categoria do ML',
							'singular_name' => 'Categoria do ML',
							'search_items' => 'Buscar Categoria do ML',
							'popular_items' => 'Categorias do ML Populares',
							'all_items' => 'Todas as Categorias do ML',
							'parent_item' => 'Categorias Pai',
							'parent_item_colon' => 'Categorias Pai:',
							'edit_item' => 'Editar Categorias do ML',
							'update_item' => 'Atualizar Categorias do ML',
							'add_new_item' => 'Adicionar Categorias do ML',
							'new_item_name' => 'Nova Categoria do ML',
					),
					'hierarchical' =>true,
					'show_ui' => true,
					'show_tagcloud' => true,
					'rewrite' => true,
					'query_var' => 'categoria_oferta',
					'public'=>true)
	);
}

add_action("manage_posts_custom_column", "my_custom_columns");
add_filter("manage_edit-oferta_columns", "oferta_columns");

function oferta_columns($columns) {
	$columns = array(
			"cb" => "<input type=\"checkbox\" />",
			"title" => "Anúncio",
			"imagem" => "Imagem",
			"preco" => "Preço",
			"categoria" => "Categoria",
			"mercadopago" => "Mercado Pago",
			"condicao" => "Condição",
			"pontos" => "Pontos Vendedor",
			"qualificacao" => "Qualificação"

	);
	return $columns;
}

function my_custom_columns($column){
	global $post;
	switch($column){
		case "preco":
			$custom = get_post_custom();
			echo number_format($custom["lojaautomatica_preco"][0],2);
			break;
		case "imagem":
			$custom = get_post_custom();
			echo '<img style="width:100px" src="'.$custom["lojaautomatica_imagem"][0].'" />';
			break;
		case "categoria":
			$custom = get_post_custom();
			$terms = array();
			
			foreach (wp_get_post_terms( $post->ID, 'oferta_categoria' ) as $term)
				$terms[] = $term->name;
			
			echo implode(',',$terms);
			
			break;
		case "mercadopago":
			$custom = get_post_custom();
			echo ($custom["lojaautomatica_mercado_pago"][0] == '1') ? 'Sim' : 'Não';
			break;
		case "condicao":
			$custom = get_post_custom();
			echo $custom["lojaautomatica_condicao"][0];
			break;
		case "pontos":
			$custom = get_post_custom();
			echo $custom["lojaautomatica_pontos_vendedor"][0];
			break;
		case "qualificacao":
			$custom = get_post_custom();
			echo $custom["lojaautomatica_percentual_vendedor"][0];
			break;
	}
}
