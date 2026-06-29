<?php
if(!defined('ABSPATH')) exit;

class DJSearch_Loader{
    public static function activate(){
        $page=get_page_by_path('djsearch-results');
        if(!$page){
            wp_insert_post(array(
                'post_title'=>'Search Results',
                'post_name'=>'djsearch-results',
                'post_status'=>'publish',
                'post_type'=>'page',
                'post_content'=>'[djsearch_results]'
            ));
        }
        flush_rewrite_rules();
    }
}
