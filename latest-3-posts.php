<?php
/**
 * File Name: Latest 3 Posts
 * Description: A WordPress Mod to display the latest three posts from a specific category.
 * Author: Alex C
 * Version: 1.0.1
 */

// Define query parameters for retrieving the latest three posts from a specific category
$args = array(
    'category_name' => 'the_category_slug', // Replace with the actual category slug
    'posts_per_page' => 3,
);

// Create a new instance of WP_Query using the defined parameters
$latest_posts_query = new WP_Query($args);

// Check if there are posts available based on the query
if ($latest_posts_query->have_posts()) :
    // Loop through each post in the query result
    while ($latest_posts_query->have_posts()) : $latest_posts_query->the_post();
?>
        <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
        <div><?php the_excerpt(); ?></div>
<?php
    endwhile;
else :
    // Display a message if no posts are found
    echo 'No posts found';
endif;

// Reset post data to restore the global post object
wp_reset_postdata();
?>
