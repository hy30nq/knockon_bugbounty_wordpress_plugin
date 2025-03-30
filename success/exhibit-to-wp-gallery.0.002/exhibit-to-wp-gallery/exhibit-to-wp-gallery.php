<?php
/*
 Plugin Name: Exhibit to WP Gallery
 Plugin URI: http://wordpress.org/extend/plugins/exhibit-to-wp-gallery/
 Description: Convert your ancient <a href="http://www.google.se/search?q=Owen+Winkler's+Exhibit">Exhibit 1.1b</a> galleries to <a href="http://wordpress.org/development/2008/03/wordpress-25-brecker/">modern</a> WP attachments/<a href="http://codex.wordpress.org/Using_the_gallery_shortcode">galleries</a>
 Version: 0.002
 Author: Ulf Benjaminsson
 Author URI: http://www.ulfben.com/
 */
add_action('admin_menu', 'ex2gal_add_option_page');
function ex2gal_get_plugin_data( $param = null ){		
	// 'Name', 'Title', 'Description', 'Author', 'Version'
	static $plugin_data;
	if ( ! $plugin_data ) {
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		$plugin_data = get_plugin_data( __FILE__ );
	}
	$output = $plugin_data;
	if($param){
		foreach((array)$plugin_data as $key => $value){
			if($param == $key){
				$output = $value;
				break;
			}
		}
	}
	return $output;
}

function ex2gal_add_admin_footer(){ //shows some plugin info at the footer of the config screen.
	$plugin_data = ex2gal_get_plugin_data();	
	printf('%1$s plugin | Version %2$s | by %3$s', $plugin_data['Title'], $plugin_data['Version'], $plugin_data['Author']);
	echo ' (who <a href="http://www.amazon.com/gp/registry/wishlist/2QB6SQ5XX2U0N/105-3209188-5640446?reveal=unpurchased&filter=all&sort=priority&layout=standard&x=21&y=17">appreciates books</a>) :)<br />';
}

function ex2gal_add_option_page() {
	if ( function_exists('add_options_page') ) {
		$plug_name = ex2gal_get_plugin_data('Name');
		add_options_page($plug_name.' Settings', $plug_name, 8, __FILE__, 'ex2gal_option_page');
		add_filter('plugin_action_links', 'ex2gal_add_plugin_actions', 10, 2 );
	}
}

function ex2gal_add_plugin_actions($links, $file){ //add's a "Settings"-link to the entry on the plugin screen
	static $this_plugin;
	if(!$this_plugin){
		$this_plugin = plugin_basename(__FILE__);
	}	
	if($file == $this_plugin){				
		$settings_link = $settings_link = '<a href="options-general.php?page='.$this_plugin.'">' . __('Settings') . '</a>';
		array_unshift($links, $settings_link);	
	}
	return $links;
}		 

function ex2gal_option_page() {
	if(function_exists('current_user_can') && !current_user_can('manage_options') ){
		die(__('Cheatin&#8217; uh?'));
	}	
	add_action('in_admin_footer', 'ex2gal_add_admin_footer');	
	if(!isset($_POST['beginfromid'])){	$_POST['beginfromid'] = 0;}
	if(!isset($_POST['max'])){	$_POST['max'] = 75;}	
	global $single, $wpdb, $exc, $id, $post, $page;	
	if(function_exists('exhibit_is_installed') && exhibit_is_installed()){
		$exc = new ex_config();
	}
	$plug_title = ex2gal_get_plugin_data('Name');
	$siteurl = get_option('siteurl');	
	$captions_to_description = isset($_POST['captions_to_description']) ? 1 : 0; //true; //will put the Exhibit caption text in the "decription" field of your attachment.
	$gallery_string = $wpdb->escape('<br />[gallery]');
	$alt = true;		
?>
<div class="wrap">
	<h2><?php echo $plug_title; ?></h2>	
	<form method="post">
	<fieldset class="options">
	<table class="optiontable">	
		<?php echo ($alt = !$alt) ? '<tr class="alternate">' : '<tr>'; ?>
		<p>'<?php echo $plug_title ?>' will help you convert your ancient Exhibit 1.1b-galleries to normal WordPress attachments.
		<ul>
		<li>Captions and image order will be transferred.</li>
		<li>All files will be <em>copied</em> to your upload folder. (use <a href="http://wordpress.org/extend/plugins/custom-upload-dir/">Custom Upload Dir</a> for better structure!)</li>
		<li>New thumbnails will be generated according to your WordPress settings</li>
		<li>Lastly, the plugin will add '&lt;br /&gt; [gallery]' to the end of each post it touches.	
		</ul>
		This process is slow and painfull, and will most likely time out - so do it in small chunks. Note where the process left off (last 'post id' reported) and use that as the starting point for the next round.</p>			
		</tr>
		<tr><td>&nbsp;</td></tr>	
		<?php 	
		if(!function_exists('exhibit_is_installed') || !exhibit_is_installed()){
			echo ($alt = !$alt) ? '<tr class="alternate">' : '<tr>';
			echo '<th><p><font color="red">Exibit doesn\'t seem to be installed</font></p></th>';
			echo '</tr>';
		} else if(isset($_POST['post_list'])){
			echo ($alt = !$alt) ? '<tr class="alternate">' : '<tr>';
			echo '<th><p>These are all your posts using Exhibit. Visit them to make sure everything went alright.</p></th>';
			echo '</tr>';
			$exhibit = $wpdb->get_results("SELECT DISTINCT post_ID FROM {$exc->tableexhibit} ORDER BY post_ID;");			
			foreach($exhibit as $p){
				$id = $p->post_ID;
				$url = get_permalink($p->post_ID);
				$title = $url;//str_replace($siteurl, '', $url);
				echo ($alt = !$alt) ? '<tr class="alternate">' : '<tr>';
				echo "<td><a href='$url' title='$title'>$title</a></td><td>&nbsp;<a href='/wp-admin/post.php?action=edit&post=$id' title='edit post'>($id)</a></td>"; 
				echo '</tr>';
			}							
		}else if(isset($_POST['submit']) && isset($_POST['max']) && isset($_POST['beginfromid'])){		
			$limit = '0,'.$wpdb->escape($_POST['max']); 
			$beginfromid = $wpdb->escape($_POST['beginfromid']);
			$exhibit = $wpdb->get_results("SELECT * FROM {$exc->tableexhibit} WHERE post_ID >= $beginfromid ORDER BY post_ID LIMIT $limit;");				
			if(count($exhibit) > 0){										
				$lastid = 0;
				$lastext = '';
				$mime = '';
				$count = 0;		
				foreach($exhibit as $p){
					$filename = basename($p->photo);
					$imageurl = $siteurl.'/'.$p->photo;
					$fullpath = ABSPATH.$p->photo;					
					$info = pathinfo($fullpath);
					$ext = $info['extension'];
					if($ext != $lastext){ //avoid running shell unless the file extension changes. 
						$file = escapeshellarg($fullpath);
						$mime = shell_exec("file -bi " . $file);
					}						
					$_FILES['ex2gal']['error'] ='';
					$_FILES['ex2gal']['tmp_name'] = $fullpath;
					$_FILES['ex2gal']['size'] = filesize($fullpath);					
					$_FILES['ex2gal']['type'] = $mime;
					$_FILES['ex2gal']['name'] = $filename;
					$_REQUEST['post_id'] = $p->post_ID; //to allow custom-upload-dir to find a good path													
					$postdata = array('post_excerpt' => $p->caption, 'menu_order' => $p->picorder);
					if($captions_to_description){
						$postdata['post_content'] = $p->caption;
					}
					echo ($alt = !$alt) ? '<tr class="alternate">' : '<tr>';
					echo '<td>';										
					$id = ex2gal_media_handle_upload('ex2gal', $p->post_ID, $postdata);					
					$url = get_permalink($p->post_ID);
					if(is_wp_error($id) || !is_numeric($id)){
						echo "<p>$count: <font color='red'>Error</font> in <a href='$url'>post ".$p->post_ID."</a>:&nbsp;".$id->errors['upload_error'][0]."</p>";						
					} else{
						echo "<p>$count: <font color='green'>Copying</font>&nbsp;".$fullpath." to <a href='$url'>post ".$p->post_ID."</a></p>";																	 						
						//now, let's add the [gallery]-tag to the post content
						if($lastid != $p->post_ID){ //avoid work if we've already touched this post during this session (session = batch)
							$lastid = $p->post_ID;
							$results = mysql_query("SELECT ID FROM $wpdb->posts WHERE post_content LIKE '%$gallery_string%' AND ID='$p->post_ID'");
							$already_have_gallery = (int)(@mysql_num_rows($results)); //make sure we haven't touched it during a previous batch.  					
							if(!$already_have_gallery){								
								$query = "UPDATE $wpdb->posts SET post_content = CONCAT(post_content, '$gallery_string') WHERE ID='$lastid'";
								$wpdb->query($query);
								echo "<span><font color='green'>Adding gallery tag.</font></span>";
							}else {
								//has gallery tag since some earlier session.
								//echo "<p><font color='orange'>Has gallery tag.</font></p>";
							}
						} else{
							//we've added the gallery tag this session.
						}
					}
					echo '</td></tr>';
					$count++;									
				}
			}
		}		
		?>		
		<tr class="alternate"><td><label>Begin from Post ID: <input type="text" value="<?php echo $_POST['beginfromid']; ?>" name="beginfromid" size="5" /></label><span style='font-size:xx-small;'> <?php $count = $wpdb->get_var("SELECT MAX(post_ID) FROM {$exc->tableexhibit}"); echo ($count) ? "The highest post id is: $count" : 'Exhibit is not installed.'; ?></span></td></tr>
		<tr class="alternate"><td><label>Max rows per batch (<100 should be safe): <input type="text" value="<?php echo $_POST['max']; ?>" name="max" size="5" /></label><span style='font-size:xx-small;'> <?php $count = $wpdb->get_var("SELECT COUNT(*) FROM {$exc->tableexhibit}"); echo ($count) ? "You've got $count rows in your exhibit table." : 'Exhibit is not installed'; ?> </span></td></tr>
		<tr class="alternate"><td><label>Use old captions as description too: <input type="checkbox" name="captions_to_description" value="1" <?php if($captions_to_description){ echo "checked";} ?> /></label><span style='font-size:xx-small;'><a href="http://wordpress.org/extend/plugins/exhibit-to-wp-gallery/screenshot-3.png"> See screenshot 3.</a></font></td></tr>
		<tr class="alternate">		
		<td><input type="submit" name="submit" title="Press the button and let the page load. It will take forever. :)" value="<?php _e('Commence...') ?>" />
		<input type="submit" name="post_list" title="Prints a list of all posts that uses Exhibit." value="<?php _e('Gimme a post list plx...') ?>" /></td>
		</tr>
		<tr><td><p><strong>Note:</strong></p>
		<ul>
		<li><font color='red'>Backup</font> both the filesystem and database before running this conversion!</li>
		<li>You might have to change permissions on the files after the conversion is done. (<a href="http://en.wikipedia.org/wiki/Chmod">chmod</a> 711 on directories, 644 on files - usually works)</li>		
		</ul>
		</td></tr>
		</table>
		</fieldset>	
		</form>		
</div>	
<?php	
}

/*
Below comes two fuglified methods I've lifted straight from WP 2.6.3. I need to tweak these to allow us to keep captions, image ordering, 
avoid duplicated attachments (if the script timeouts and we need to restart the process), "upload" from the local filesystem and so on. 

I've tried to comment my overrides and changes.

Putting these at the bottom of the file simply 'cause they're a pain to scroll past. :)
*/

//ULFBEN: Lifted from wp-admin/includes/media.php, WP 2.6.3
// this handles the file upload POST itself, creating the attachment post
function ex2gal_media_handle_upload($file_id, $post_id, $post_data = array()) {
	$overrides = array('test_form'=>false);
	$file = ex2gal_wp_handle_upload($_FILES[$file_id], $overrides);

	if ( isset($file['error']) )
		return new WP_Error( 'upload_error', $file['error'] );

	$url = $file['url'];
	$type = $file['type'];
	$file = $file['file'];
	$title = preg_replace('/\.[^.]+$/', '', basename($file));
	$content = '';

	// use image exif/iptc data for title and caption defaults if possible
	if ( $image_meta = @wp_read_image_metadata($file) ) {
		if ( trim($image_meta['title']) )
			$title = $image_meta['title'];
		if ( trim($image_meta['caption']) )
			$content = $image_meta['caption'];
	}

	// Construct the attachment array
	$attachment = array_merge( array(
		'post_mime_type' => $type,
		'guid' => $url,
		'post_parent' => $post_id,
		'post_title' => $title,
		'post_content' => $content,
	), $post_data );	
		
	// Save the data
	$id = wp_insert_attachment($attachment, $file, $post_parent);
	if ( !is_wp_error($id) ) {
		wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $file ) );
	}
	return $id;
}

//ULFBEN: lifted from wp-admin/includes/file.php, WP 2.6.3
function ex2gal_wp_handle_upload( &$file, $overrides = false ) {
	// The default error handler.
	if (! function_exists( 'wp_handle_upload_error' ) ) {
		function wp_handle_upload_error( &$file, $message ) {
			return array( 'error'=>$message );
		}
	}
	// You may define your own function and pass the name in $overrides['upload_error_handler']
	$upload_error_handler = 'wp_handle_upload_error';

	// $_POST['action'] must be set and its value must equal $overrides['action'] or this:
	$action = 'wp_handle_upload';

	// Courtesy of php.net, the strings that describe the error indicated in $_FILES[{form field}]['error'].
	$upload_error_strings = array( false,
		__( "The uploaded file exceeds the <code>upload_max_filesize</code> directive in <code>php.ini</code>." ),
		__( "The uploaded file exceeds the <em>MAX_FILE_SIZE</em> directive that was specified in the HTML form." ),
		__( "The uploaded file was only partially uploaded." ),
		__( "No file was uploaded." ),
		__( "Missing a temporary folder." ),
		__( "Failed to write file to disk." ));

	// All tests are on by default. Most can be turned off by $override[{test_name}] = false;
	$test_form = true;
	$test_size = true;

	// If you override this, you must provide $ext and $type!!!!
	$test_type = true;
	$mimes = false;

	// Install user overrides. Did we mention that this voids your warranty?
	if ( is_array( $overrides ) )
		extract( $overrides, EXTR_OVERWRITE );

	// A correct form post will pass this test.
	if ( $test_form && (!isset( $_POST['action'] ) || ($_POST['action'] != $action ) ) )
		return $upload_error_handler( $file, __( 'Invalid form submission.' ));
	
	// A successful upload will pass this test. It makes no sense to override this one.
	if ( $file['error'] > 0 )
		return $upload_error_handler( $file, $upload_error_strings[$file['error']] );
	
	// A non-empty file will pass this test.
	if ( $test_size && !($file['size'] > 0 ) )
		return $upload_error_handler( $file, __( 'File is empty. Please upload something more substantial. This error could also be caused by uploads being disabled in your php.ini.' ));
		
	// A properly uploaded file will pass this test. There should be no reason to override this one.
	/*ULFBEN if (! @ is_uploaded_file( $file['tmp_name'] ) )
		return $upload_error_handler( $file, __( 'Specified file failed upload test.' ));*/

	// A correct MIME type will pass this test. Override $mimes or use the upload_mimes filter.
	if ( $test_type ) {
		$wp_filetype = wp_check_filetype( $file['name'], $mimes );
		extract( $wp_filetype );
		if ( ( !$type || !$ext ) && !current_user_can( 'unfiltered_upload' ) )
			return $upload_error_handler( $file, __( 'File type does not meet security guidelines. Try another.' ));
		if ( !$ext )
			$ext = ltrim(strrchr($file['name'], '.'), '.');
		if ( !$type )
			$type = $file['type'];
	}
	// A writable uploads dir will pass this test. Again, there's no point overriding this one.
	if(!(($uploads = wp_upload_dir() ) && false === $uploads['error'] ) )
		return $upload_error_handler( $file, $uploads['error'] );
	
	/*ULFBEN - avoid adding duplicates. (so we can safely retry if it timeouts or whatever*/
	$filename = strtolower($file['name']);
	$info = pathinfo($filename);
	$ext = $info['extension'];
	$name = basename($filename, ".{$ext}");
	if ( empty( $ext ) ){
		$ext = '';
	}else{
		$ext = strtolower( ".$ext" );
	}
	$filename = str_replace( $ext, '', $filename );
	// Strip % so the server doesn't try to decode entities.
	$filename = str_replace('%', '', sanitize_title_with_dashes( $filename ) ) . $ext;
	if(file_exists($uploads['path']."/$filename")){		// slash = assumes *NIX-host	
		return $upload_error_handler( $file, 'File already exists: <font color="blue">'.$filename.'</font>. <font color="orange">Skipping.</font>' );
	}		
	/*END ULFBEN*/	
	$filename = wp_unique_filename( $uploads['path'], $file['name'], $unique_filename_callback );

	// Move the file to the uploads dir
	$new_file = $uploads['path'] . "/$filename";	
	if(false === @ copy( $file['tmp_name'], $new_file) ) {		
		return $upload_error_handler( $file, sprintf( __('The uploaded file could not be moved to %s.' ), $uploads['path'] ) );
	}
	
	// Set correct file permissions
	$stat = stat( dirname( $new_file ));
	$perms = $stat['mode'] & 0000666;
	//@ chmod( $new_file, $perms );
	@chmod( $new_file, 0644 ); //ULFBEN (you still need to chmod the WP-generated thumbs though...)
	
	// Compute the URL
	$url = $uploads['url'] . "/$filename";

	$return = apply_filters( 'wp_handle_upload', array( 'file' => $new_file, 'url' => $url, 'type' => $type ) );

	return $return;
}

 
?>