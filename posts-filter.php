<?php
/*
Plugin Name: Posts Filter
Description: Filter posts based on missing featured image, tags, author, or categories.
Version: 1.1
Author: Olayinka
Author URI: https://github.com/aremuolayinka02
Plugin URI: https://github.com/aremuolayinka02/posts-filter
*/

if (!defined('ABSPATH')) exit;

// Add main menu and submenus
add_action('admin_menu', function() {
    add_menu_page(
        'Posts Filter',
        'Posts Filter',
        'manage_options',
        'posts-filter',
        'pf_posts_page',
        'dashicons-filter',
        25
    );
    add_submenu_page(
        'posts-filter',
        'Posts',
        'Posts',
        'manage_options',
        'posts-filter',
        'pf_posts_page'
    );
    add_submenu_page(
        'posts-filter',
        'Settings',
        'Settings',
        'manage_options',
        'posts-filter-settings',
        'pf_settings_page'
    );
});

// Register settings
add_action('admin_init', function() {
    register_setting('pf_settings_group', 'pf_settings');
    register_setting('pf_settings_group', 'pf_allowed_roles');
});

// Settings page
function pf_settings_page() {
    $options = get_option('pf_settings', [
        'no_featured_image' => 0,
        'no_tags' => 0,
        'no_author' => 0,
        'no_categories' => 0,
    ]);
    
    // Get all WordPress roles
    $wp_roles = wp_roles();
    $allowed_roles = get_option('pf_allowed_roles', ['administrator']); // Default to administrator
    ?>
<div class="wrap">
    <h1>Posts Filter Settings</h1>
    <form method="post" action="options.php">
        <?php settings_fields('pf_settings_group'); ?>

        <h2>Filter Settings</h2>
        <table class="form-table">
            <tr>
                <th scope="row">Posts with no featured image</th>
                <td>
                    <input type="checkbox" name="pf_settings[no_featured_image]" value="1"
                        <?php checked(1, $options['no_featured_image']); ?> />
                </td>
            </tr>
            <tr>
                <th scope="row">Posts with no tags</th>
                <td>
                    <input type="checkbox" name="pf_settings[no_tags]" value="1"
                        <?php checked(1, $options['no_tags']); ?> />
                </td>
            </tr>
            <tr>
                <th scope="row">Posts with no author</th>
                <td>
                    <input type="checkbox" name="pf_settings[no_author]" value="1"
                        <?php checked(1, $options['no_author']); ?> />
                </td>
            </tr>
            <tr>
                <th scope="row">Posts with no categories</th>
                <td>
                    <input type="checkbox" name="pf_settings[no_categories]" value="1"
                        <?php checked(1, $options['no_categories']); ?> />
                </td>
            </tr>
        </table>

        <h2>Role Access Settings</h2>
        <p>Select which user roles can access the Posts Filter:</p>
        <table class="form-table">
            <?php foreach ($wp_roles->roles as $role_key => $role): ?>
            <tr>
                <th scope="row"><?php echo esc_html($role['name']); ?></th>
                <td>
                    <input type="checkbox" name="pf_allowed_roles[]" value="<?php echo esc_attr($role_key); ?>"
                        <?php checked(in_array($role_key, $allowed_roles)); ?>
                        <?php if ($role_key === 'administrator') echo 'checked disabled'; ?> />
                    <?php if ($role_key === 'administrator') echo '<input type="hidden" name="pf_allowed_roles[]" value="administrator">'; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>

        <?php submit_button(); ?>
    </form>
</div>
<?php
}

// Check if user has access to Posts Filter
function pf_user_has_access() {
    $allowed_roles = get_option('pf_allowed_roles', ['administrator']);
    $user = wp_get_current_user();
    
    foreach ($user->roles as $role) {
        if (in_array($role, $allowed_roles)) {
            return true;
        }
    }
    return false;
}

// Posts page
function pf_posts_page() {
    // Check if user has access
    if (!pf_user_has_access()) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    $options = get_option('pf_settings', []);
    $args = [
        'post_type' => 'post',
        'posts_per_page' => 50,
        'post_status' => 'any',
    ];

    $posts = get_posts($args);

    $filtered_posts = [];
    foreach ($posts as $post) {
        $include = true;

        if (!empty($options['no_featured_image'])) {
            if (has_post_thumbnail($post->ID)) $include = false;
        }
        if (!empty($options['no_tags'])) {
            $tags = wp_get_post_tags($post->ID);
            if (!empty($tags)) $include = false;
        }
        if (!empty($options['no_author'])) {
            $author = get_the_author_meta('ID', $post->post_author);
            if (!empty($author)) $include = false;
        }
        if (!empty($options['no_categories'])) {
            $cats = wp_get_post_categories($post->ID);
            if (!empty($cats)) $include = false;
        }

        if ($include) $filtered_posts[] = $post;
    }
    ?>
<div class="wrap">
    <h1>Filtered Posts</h1>
    <?php if (empty($filtered_posts)): ?>
    <p>No posts found with the current filter settings.</p>
    <?php else: ?>
    <table class="widefat">
        <thead>
            <tr>
                <th>Title</th>
                <th>Author</th>
                <th>Date</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($filtered_posts as $post): ?>
            <tr>
                <td><a
                        href="<?php echo get_edit_post_link($post->ID); ?>"><?php echo esc_html($post->post_title); ?></a>
                </td>
                <td><?php echo esc_html(get_the_author_meta('display_name', $post->post_author)); ?></td>
                <td><?php echo esc_html(get_the_date('', $post->ID)); ?></td>
                <td><?php echo esc_html($post->post_status); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
<?php
}

// Add menu items only if user has access
add_action('admin_menu', function() {
    if (pf_user_has_access()) {
        add_menu_page(
            'Posts Filter',
            'Posts Filter',
            'read',  // Changed from 'manage_options' to 'read'
            'posts-filter',
            'pf_posts_page',
            'dashicons-filter',
            25
        );
        add_submenu_page(
            'posts-filter',
            'Posts',
            'Posts',
            'read',  // Changed from 'manage_options' to 'read'
            'posts-filter',
            'pf_posts_page'
        );
    }
    
    // Settings page only for administrators
    if (current_user_can('manage_options')) {
        add_submenu_page(
            'posts-filter',
            'Settings',
            'Settings',
            'manage_options',
            'posts-filter-settings',
            'pf_settings_page'
        );
    }
});