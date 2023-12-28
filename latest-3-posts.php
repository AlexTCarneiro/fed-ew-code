<?php
// Display the latest three posts from a specific category

$args = array(
    'category_name' => 'the_category_slug', // Replace with the actual category slug
    'posts_per_page' => 3,
);

$latest_posts_query = new WP_Query($args);

if ($latest_posts_query->have_posts()) :
    while ($latest_posts_query->have_posts()) : $latest_posts_query->the_post();
?>
        <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
        <div><?php the_excerpt(); ?></div>
<?php
    endwhile;
else :
    echo 'No posts found';
endif;

wp_reset_postdata();
?>
