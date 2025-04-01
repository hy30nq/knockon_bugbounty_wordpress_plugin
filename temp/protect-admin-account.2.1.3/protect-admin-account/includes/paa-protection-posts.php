<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! defined( 'THP_PAA_PLUGIN_DIR' ) ) exit;

if ( !function_exists( 'thp_paa_protect_admin_posts' ) ) {
	function thp_paa_protect_admin_posts ( $allcaps, $cap, $args ) {
		$thp_paa_options = get_option( 'thp_paa_options' );

		if ( $thp_paa_options && !empty($thp_paa_options['protect_posts']) ) {
			$requested_cap = $args[0];
			$user_id = $args[1];
			$maybe_post = isset($args[2]) && !empty($args[2]) ? $args[2] : false;

			$posts_block_cap = array(
				'edit_post',
				'edit_posts',
				'edit_others_posts',
				'edit_published_posts',
				'edit_private_posts',

				'delete_post',
				'delete_posts',
				'delete_others_posts',
				'delete_published_posts',
				'delete_private_post',
			);
				
			if ( !empty( $maybe_post ) ) {
				$maybe_get_post = get_post( $maybe_post );
				if ( $maybe_get_post && $maybe_get_post->post_author && $maybe_get_post->post_type == 'post' ) {
					$author_id = $maybe_get_post->post_author;

					if ( in_array( $requested_cap, $posts_block_cap ) ) {
						if ( 
							!empty( $thp_paa_options['protected_users'][$author_id] ) // author is a protected user
							&& empty( $thp_paa_options['protected_users'][$user_id] ) // user is not a protected user
							&& $user_id != $author_id // user is not the author
						) {
							foreach ( $posts_block_cap as $block_post_cap ) {
								$allcaps[$block_post_cap] = false;
							}
						}
					}
				}
			}
		}

		return $allcaps;
	}
	add_filter( 'user_has_cap', 'thp_paa_protect_admin_posts', 10, 3 );
}
