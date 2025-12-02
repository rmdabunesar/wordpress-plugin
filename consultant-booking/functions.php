<?php 

/**
 * Get and include template file
 */
function cb_get_template( $template_slug, $args = [] ){
    
    $template_name = 'consultant-booking/' . $template_slug . '.php';
    $template = locate_template( $template_name );

    if( !$template ){
        $template = CB_PLUGIN_DIR . 'templates/' .$template_slug. '.php';
    }


    if( file_exists( $template ) ){
        extract( $args, EXTR_SKIP );
        include $template;
    }

}


add_action('cb_before_form', function(){
    echo 'Booking form befor';
});

add_action('cb_after_form', function(){
    echo 'Booking form after';
});