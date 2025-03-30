$lojaautomatica = jQuery.noConflict();

$lojaautomatica(function() {

	$lojaautomatica('.cat2,.cat3,.cat4').hide();
	populate_cats('', 'categorias-1', 1);

});

function populate_cats(cat, element, depth){
	
	if (cat.length <= 1){
	
		var more = (depth > 1) ? '.category' : '';
		
		$lojaautomatica.getJSON("http://query.yahooapis.com/v1/public/yql?q=select%20*%20from%20xml%20where%20url%3D'http%3A%2F%2Fwww.mercadolivre.com.br%2Fjm%2FcategsXml%3Fas_site_id%3DMLB%26as_max_level%3D"+depth+"%26as_categ_id%3D"+cat+"'%20AND%20itemPath%3D'response.categories.category"+more+"'&format=json&callback=", function(data) {
	
			$lojaautomatica(data.query.results.category).each(function (i) {
				$lojaautomatica('#'+element).append('<option value="'+this.id+'">'+this.name+'</option>');  
			});
	
		});
	}
}

//$lojaautomatica('#texto_categorias').append('<div id="cat-'+$lojaautomatica(this).val()+'">'+$lojaautomatica(this).find('option:selected').text()+'</div>');


$lojaautomatica("#categorias-1").live("change", function(){
	//$lojaautomatica('#category_ml').val($lojaautomatica(this).val());
	//$lojaautomatica('#texto_categorias').append($lojaautomatica(this).find('option:selected').text());
	
	$lojaautomatica('.cat2').show();
	$lojaautomatica('.cat3').hide();
	$lojaautomatica('.cat4').hide();
	
	if ($lojaautomatica(this).find('option:selected').length > 1){
		$lojaautomatica('.cat2').hide();
	}

	$lojaautomatica('#categorias-2').html('');
	$lojaautomatica('#categorias-3').html('');
	$lojaautomatica('#categorias-4').html('');
	populate_cats($lojaautomatica(this).val(), 'categorias-2', 2);
	
});

$lojaautomatica("#categorias-2").live("change", function(){
	//$lojaautomatica('#category_ml').val($lojaautomatica(this).val());
	//$lojaautomatica('#texto_categoria').html($lojaautomatica(this).find('option:selected').text());
	
	$lojaautomatica('.cat3').show();
	$lojaautomatica('.cat4').hide();
	
	if ($lojaautomatica(this).find('option:selected').length > 1){
		$lojaautomatica('.cat3').hide();
	}
	
	$lojaautomatica('#categorias-3').html('');
	$lojaautomatica('#categorias-4').html('');
	populate_cats($lojaautomatica(this).val(), 'categorias-3', 2);
	
});

$lojaautomatica("#categorias-3").live("change", function(){
	//$lojaautomatica('#category_ml').val($lojaautomatica(this).val());
	//$lojaautomatica('#texto_categoria').html($lojaautomatica(this).find('option:selected').text());
	
	$lojaautomatica('.cat4').show();
	
	if ($lojaautomatica(this).find('option:selected').length > 1){
		$lojaautomatica('.cat4').hide();
	}
	
	$lojaautomatica('#categorias-4').html('');
	populate_cats($lojaautomatica(this).val(), 'categorias-4', 2);
	
});

$lojaautomatica("#categorias-4").live("change", function(){
	//$lojaautomatica('#category_ml').val($lojaautomatica(this).val());
	//$lojaautomatica('#texto_categoria').html($lojaautomatica(this).find('option:selected').text());	
});

$lojaautomatica(".addcat").live("click", function(){
	var id = $lojaautomatica(this).attr('id').split('-')[1];
	var cats = $lojaautomatica('#categoria_ml').val().split(',');
	//var cats_nomes = $lojaautomatica('#categoria_ml_nomes').val().split(',');
	
	$lojaautomatica('#categorias-'+id+' option:selected').each(function(index) {
		$lojaautomatica('#texto_categorias').append($lojaautomatica(this).text()+' [ID: '+$lojaautomatica(this).val()+']<br/>');
		cats.push($lojaautomatica(this).val());
		//cats_nomes.push($lojaautomatica(this).text());
	});
	$lojaautomatica('.cat2,.cat3,.cat4').hide();
	$lojaautomatica('#categoria_ml').val(cats.join(','));
	//$lojaautomatica('#categoria_ml_nomes').val(cats_nomes.join(','));
});

