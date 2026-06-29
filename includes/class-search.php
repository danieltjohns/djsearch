<?php
require_once __DIR__ . '/class-render.php';

if(!defined('ABSPATH')) exit;
class DJSearch_Search{
public static function results(){

if(!isset($_GET['djsearch_query'])) return;

$search = sanitize_text_field($_GET['djsearch_query']);
$isbn_clean = preg_replace('/[^0-9Xx]/', '', $search);

$sku_query = new WP_Query([
'post_type'=>['product','product_variation'],
'posts_per_page'=>10,
'post_status'=>'publish',
'meta_query'=>[
'relation'=>'OR',
['key'=>'_sku','value'=>$isbn_clean,'compare'=>'='],
['key'=>'_sku','value'=>$isbn_clean,'compare'=>'LIKE']
]
]);


$ranked_ids = [];

$title_query = new WP_Query([
'post_type'=>['product','page'],
'posts_per_page'=>200,
'post_status'=>'publish',
'no_found_rows'=>true,
'fields'=>'ids'
]);

$s = strtolower(trim($search));

foreach($title_query->posts as $pid){
    $title = strtolower(trim(get_the_title($pid)));

    if($title === $s){
        $ranked_ids[$pid] = 900;
    } elseif(strpos($title,$s) === 0){
        $ranked_ids[$pid] = 800;
    } elseif(strpos($title,$s) !== false){
        $ranked_ids[$pid] = 700;
    }
}

arsort($ranked_ids);

$normal_query = new WP_Query([
'post_type'=>['product','page'],
'posts_per_page'=>50,
'no_found_rows'=>true,
'update_post_meta_cache'=>false,
'update_post_term_cache'=>false,
'post_status'=>'publish',
's'=>$search
]);

$results=array_keys($ranked_ids);

if($sku_query->have_posts()){
while($sku_query->have_posts()){
$sku_query->the_post();

$id=get_the_ID();

if(get_post_type($id)==='product_variation'){
$id=wp_get_post_parent_id($id);
}

if(!in_array($id,$results)){
$results[]=$id;
}
}}
wp_reset_postdata();

if($normal_query->have_posts()){
while($normal_query->have_posts()){
$normal_query->the_post();

$id=get_the_ID();

if(!in_array($id,$results)){
$results[]=$id;
}
}}
wp_reset_postdata();



$grouped=[];

foreach($results as $id){

if(get_post_type($id)==='product'){

$terms=get_the_terms($id,'product_cat');

if($terms){
foreach($terms as $term){
$grouped[$term->name][]=$id;
}}

}else{
$grouped['Pages'][]=$id;
}
}


/* prioritize searched category */
$matched_category = null;

foreach(array_keys($grouped) as $group_name){
    if(strtolower($group_name) === strtolower($search)){
        $matched_category = $group_name;
        break;
    }
}

uksort($grouped,function($a,$b) use($matched_category){

if($matched_category){

    if($a === $matched_category && $b !== $matched_category){
        return -1;
    }

    if($b === $matched_category && $a !== $matched_category){
        return 1;
    }

}

return strcasecmp($a,$b);

});



// V7.7 relevance scoring
$top_match_id = 0;
$best_score = 0;

foreach($results as $rid){
    $title = strtolower(trim(get_the_title($rid)));
    $search_l = strtolower(trim($search));
    $score = 0;

    $sku_val = strtolower(trim((string)get_post_meta($rid,'_sku',true)));

    if(!empty($isbn_clean) && $sku_val === strtolower($isbn_clean)){
        $score = 1000;
    } elseif($title === $search_l){
        $score = 900;
    } elseif(strpos($title,$search_l) === 0){
        $score = 800;
    } elseif(strpos($title,$search_l) !== false){
        $score = 700;
    }

    if($score > $best_score){
        $best_score = $score;
        $top_match_id = $rid;
    }
}


ob_start();

$total_found = 0;
foreach($grouped as $tmp_posts){
    $total_found += count($tmp_posts);
}

if($total_found === 0){

echo DJSearch_Render::no_results($search);

$suggest = new WP_Query(array(
    'post_type' => 'product',
    'post_status' => 'publish',
    'posts_per_page' => 12,
    'orderby' => 'rand'
));

if($suggest->have_posts()){

    while($suggest->have_posts()){
        $suggest->the_post();

        $id = get_the_ID();

        $terms = get_the_terms($id,'product_cat');

        if($terms){

            $term = reset($terms);

            $grouped[$term->name][] = $id;
        }
    }

    wp_reset_postdata();
}

}



echo DJSearch_Render::search_header($search);

if($top_match_id > 0){
$link=get_permalink($top_match_id);
$sku=get_post_meta($top_match_id,'_sku',true);
$image=$sku ? 'https://ping2.batch.co.uk/blcover/l/'.$sku.'.jpg' : get_the_post_thumbnail_url($top_match_id,'medium');
echo '<div class="djsearch-top-match">';
echo '<h2>Top Match</h2>';
echo '<div class="djsearch-single-layout">';
echo '<div class="djsearch-single-image">';
if($image){ echo '<a href="'.esc_url($link).'"><img class="djsearch-image" src="'.esc_url($image).'"></a>'; }
echo '</div>';
echo '<div class="djsearch-single-text">';
echo '<h3><a href="'.esc_url($link).'">'.esc_html(get_the_title($top_match_id)).'</a></h3>';

$short_description = '';
if(function_exists('wc_get_product')){
    $product = wc_get_product($top_match_id);
    if($product){
        $short_description = $product->get_short_description();
    }
}
if(empty($short_description)){
    $short_description = get_the_excerpt($top_match_id);
}
if($short_description){
    echo '<div class="djsearch-description">'.wp_kses_post(strip_shortcodes($short_description)).'</div>';
}
echo '</div></div></div>';
}


foreach($grouped as $category=>$posts){

$term=get_term_by('name',$category,'product_cat');

echo '<div class="djsearch-section">';

if($term&&!is_wp_error($term)){
echo '<h2 class="djsearch-category"><a href="'.esc_url(get_term_link($term)).'">'.esc_html($category).'</a></h2>';
}else{
echo '<h2 class="djsearch-category">'.esc_html($category).'</h2>';
}

$count = count($posts);
$grid_class = 'djsearch-grid';

if($count == 1){
    $grid_class .= ' single-result';
}

if($count == 2){
    $grid_class .= ' two-results';
}

echo '<div class="'.$grid_class.'">';

foreach($posts as $id){

$link=get_permalink($id);
if(!$link) continue;

$sku=get_post_meta($id,'_sku',true);

$image=$sku
? 'https://ping2.batch.co.uk/blcover/l/'.$sku.'.jpg'
: get_the_post_thumbnail_url($id,'medium');


$is_fav=false;
$terms=get_the_terms($id,'product_cat');
if($terms){
foreach($terms as $t){
if(in_array($t->name,array('Tori','Lucy','Jeremy','Jackie B','Grace','Dan','Claire'))){
$is_fav=true;
break;
}}}
$short_description='';


if($count == 1){

if(function_exists('wc_get_product')){
$product = wc_get_product($id);

if($product){
$short_description = $product->get_short_description();
}
}

if(empty($short_description)){
$short_description = get_the_excerpt($id);
}
}

echo '<div class="djsearch-item">';

if($is_fav){
echo '<div class="djsearch-badge">Bookseller Favourite</div>';
}


if($count == 1){


echo '<div class="djsearch-single-layout">';

echo '<div class="djsearch-single-image">';

echo '<a href="'.esc_url($link).'">';

if($image){
echo '<img class="djsearch-image" src="'.esc_url($image).'">';
}

echo '</a>';

echo '</div>';

echo '<div class="djsearch-single-text">';

echo '<h3><a href="'.esc_url($link).'">'.esc_html(get_the_title($id)).'</a></h3>';

if($short_description){
echo '<div class="djsearch-description">'.wp_kses_post(strip_shortcodes($short_description)).'</div>';
}

echo '</div></div>';

}else{

echo '<a href="'.esc_url($link).'">';


if($image){
echo '<img class="djsearch-image" src="'.esc_url($image).'">';
}

echo '<h3>'.esc_html(get_the_title($id)).'</h3>';

echo '</a>';

}

echo '</div>';
}

echo '</div></div>';
}

return DJSearch_Render::output(ob_get_clean());


}
}
