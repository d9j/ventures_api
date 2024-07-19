<?php

/**
 * @package ventures_api
 * @version 1.1.1
 */
/*
Plugin Name: Ventures Api
Author: Ventures Api
Version: 1.0.0
*/


// https://dalenguyen.medium.com/how-to-get-featured-image-from-wordpress-rest-api-5e023b9896c6


add_filter( 'rest_prepare_post', 'add_featured_image_to_rest_response', 10, 3 );

function add_featured_image_to_rest_response( $response, $post, $request ) {
    // Check if the post has a featured image
    if ( has_post_thumbnail( $post->ID ) ) {
        // Get the featured image URL
        $featured_image_url = get_the_post_thumbnail_url( $post->ID, 'full' );
    } else {
        $featured_image_url = null;
    }

    $author_name = get_the_author_meta( 'display_name', $post->post_author );

    // Add the featured image URL, short description, and author name to the REST API response
    $response->data['featured_image_url'] = $featured_image_url;
    $response->data['author_name'] = $author_name;

    // Add the featured image URL to the REST API response
    $response->data['featured_image_url'] = $featured_image_url;



    return $response;
}


// Function to add custom category data to REST API responses
function custom_category_data_rest_api_init() {
    // Register the field to include in the response
    register_rest_field( 'post',
        'category_data',
        array(
            'get_callback'    => 'custom_get_post_category_data',
            'update_callback' => null,
            'schema'          => null,
        )
    );
}

// Callback to get custom category data
function custom_get_post_category_data( $object, $field_name, $request ) {
    // Get category IDs for the post
    $category_ids = wp_get_post_categories( $object['id'] );

    // Get category names
    $categories = array();
    foreach ( $category_ids as $cat_id ) {
        $cat = get_category( $cat_id );
        if ( $cat ) {
            $categories[] = array(
                'id'   => $cat->term_id,
                'name' => $cat->name,
            );
        }
    }

    return $categories;
}

// Hook into the REST API initialization action to add custom field
add_action( 'rest_api_init', 'custom_category_data_rest_api_init' );




// Add a custom field to category edit form
function add_category_name_en($tag) {
    // Check for existing custom field value
    $term_id = $tag->term_id;
    $name_en = get_term_meta($term_id, 'name_en', true);
    ?>
    <tr class="form-field">
        <th scope="row" valign="top"><label for="name_en"><?php _e('英文名'); ?></label></th>
        <td>
            <input type="text" name="name_en" id="name_en" value="<?php echo esc_attr($name_en) ? esc_attr($name_en) : ''; ?>" />
            <p class="description"><?php _e(""); ?></p>
        </td>
    </tr>
    <?php
}
add_action('category_edit_form_fields', 'add_category_name_en');

// Save the custom field value
function save_category_name_en($term_id) {
    if (isset($_POST['name_en'])) {
        update_term_meta($term_id, 'name_en', sanitize_text_field($_POST['name_en']));
    }
}
add_action('edited_category', 'save_category_name_en');
add_action('create_category', 'save_category_name_en');



function register_custom_category_field() {
    register_rest_field(
        'category',
        'name_en',
        array(
            'get_callback' => 'get_custom_category_field',
            'update_callback' => null,
            'schema' => null,
        )
    );
}
add_action('rest_api_init', 'register_custom_category_field');

// Callback function to get the custom field value
function get_custom_category_field($object) {
    // Get the term ID from the category object
    $term_id = $object['id'];
    // Return the custom field value
    return get_term_meta($term_id, 'name_en', true);
}

// post tags

function add_tags_to_rest_response() {
    register_rest_field(
        'post', // The post type to which we are adding the field
        'tags', // The name of the new field
        array(
            'get_callback'    => 'get_post_tags', // The callback function that retrieves the field value
            'schema'          => null,
        )
    );
}

function get_post_tags($object, $field_name, $request) {
    $tags = wp_get_post_tags($object['id']); // Get the tags for the post
    if (empty($tags)) {
        return [];
    }
    return $tags;
}

// Hook into the REST API
add_action('rest_api_init', 'add_tags_to_rest_response');

///  Careers
function wporg_careers_post_type() {
	register_post_type('wporg_career_posts',
		array(
            'supports' => array('title', 'editor', 'excerpt', 'author', 'thumbnail', 'revisions', 'custom-fields','tags'),

			'labels'      => array(
				'name'          => __('招聘', 'textdomain'),
				'singular_name' => __('招聘', 'textdomain'),
			),
            'taxonomies' => array('post_tag'), // Add tag support
				'public'      => true,

				'has_archive' => true,
                'menu_position' => 5,
                'rewrite' => array('slug' => 'careers'), // Custom rewrite settings
                'show_in_rest' => true, // Ensure the post type is accessible via the REST API
                'rest_base' => 'careers', // Optional: customize the REST API base route
                'rest_controller_class' => 'WP_REST_Posts_Controller', // Use the default REST controller
		)
	);

}
add_action('init', 'wporg_careers_post_type');


function register_careers_tag_endpoint() {
    register_rest_route('custom/v1', '/career-tags', array(
        'methods' => 'GET',
        'callback' => 'get_career_tags',
        'permission_callback' => function () {
      return true;
    }

    )

);

}
add_action('rest_api_init', 'register_careers_tag_endpoint');

function get_career_tags() {
    $tags_query = new WP_Term_Query(array(
        'taxonomy' => 'post_tag',
        'object_ids' => get_posts(array(
            'post_type' => 'wporg_career_posts',
            'fields' => 'ids',
            'posts_per_page' => -1,
        )),
    ));

    $tags = $tags_query->get_terms();

    $formatted_tags = array_map(function ($tag) {
        return array(
            'id' => $tag->term_id,
            'name' => $tag->name,
            'slug' => $tag->slug,
            // Add more fields if needed
        );
    }, $tags);

    return rest_ensure_response($formatted_tags);
}


