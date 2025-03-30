<?php
    add_filter('plugin_action_links', 'mal_membership_action_links', 10, 2);
    //add_filter('plugin_row_meta', 'mal_membership_donate_link', 10, 2);

function mal_membership_action_links($links, $file) {
    static $this_plugin;

    if (!$this_plugin) {
        $this_plugin = plugin_basename(__FILE__);
    }

    if ($file == 'mal-membership/mal-membership.php') {
        // The "page" query string value must be equal to the slug
        // of the Settings admin page we defined earlier, which in
        // this case equals "myplugin-settings".
        $settings_link = '<a href="' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=mal_membership_settings">Settings</a>';
        array_unshift($links, $settings_link);
    }

    return $links;
}

