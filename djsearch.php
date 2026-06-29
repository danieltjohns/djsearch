<?php
/*
Plugin Name: DJSearch
Description: Stable WooCommerce search with refined layouts and ISBN/SKU support.
Version: 8.4
Author: Daniel Johns
*/

if (!defined('ABSPATH')) exit;

require_once plugin_dir_path(__FILE__).'includes/class-loader.php';
require_once plugin_dir_path(__FILE__).'includes/class-search.php';

register_activation_hook(__FILE__, ['DJSearch_Loader','activate']);

function djsearch_form(){
return '<form method="get" action="'.site_url('/djsearch-results/').'">
<div class="djsearch-input-wrap">
<input type="text" name="djsearch_query" placeholder="Search..." required>
<button type="submit" class="djsearch-btn">🔍</button>
</div>
</form>';
}

add_filter('wp_nav_menu_items', function($items){
    return $items.'<li class="menu-item djsearch-menu">'.djsearch_form().'</li>';
},10,2);

function djsearch_results_shortcode(){
    return DJSearch_Search::results();
}
add_shortcode('djsearch_results','djsearch_results_shortcode');

add_action('wp_head', function(){

echo '<style>

.djsearch-input-wrap{
display:flex;
border:1px solid #ddd;
border-radius:16px;
height:30px;
overflow:hidden;
}

.djsearch-input-wrap input{
border:none;
padding:4px 8px;
font-size:13px;
width:120px;
outline:none;
}

.djsearch-btn{
background:none;
border:none;
cursor:pointer;
padding:4px 6px;
font-size:12px;
}

.djsearch-grid{
display:grid;
grid-template-columns:repeat(auto-fit,minmax(180px,1fr));
gap:20px;
margin-top:20px;
}



.djsearch-image{
width:100%;
height:auto;
display:block;
}

.djsearch-grid.single-result{
display:block;
}

.djsearch-single-layout{
display:flex;
align-items:flex-start;
gap:30px;
max-width:1000px;
margin:0 auto;
text-align:left;
}

.djsearch-single-image{
flex:0 0 25%;
}

.djsearch-single-image img{
width:100%;
}

.djsearch-single-text{
flex:1;
display:flex;
flex-direction:column;
justify-content:flex-start;
align-self:flex-start;
}

.djsearch-single-text h3{
margin:0 0 12px 0;
line-height:1.1;
font-size:28px;
}

.djsearch-single-text h3 a{
text-decoration:none;
color:inherit;
}

.djsearch-description{
line-height:1.6;
font-size:0.8em;
color:#444;
}

.djsearch-description a{
pointer-events:none;
text-decoration:none;
color:inherit;
cursor:default;
}

.djsearch-grid.two-results{
display:flex;
justify-content:center;
align-items:flex-start;
gap:30px;
width:50%;
max-width:50%;
margin:20px auto;
}

.djsearch-grid.two-results 

.djsearch-grid.two-results .djsearch-image{
width:60%;
max-width:220px;
margin:0 auto;
display:block;
}




.djsearch-item{
text-align:center;
position:relative;
padding-top:40px;
}


.djsearch-badge{
position:absolute;
top:0;
left:50%;
transform:translateX(-50%);
background:#000;
color:#fff;
font-size:14px;
font-weight:bold;
padding:6px 8px;
border-radius:4px;
white-space:nowrap;
}

.djsearch-category a{
text-decoration:none;
color:inherit;
}

@media(max-width:768px){

.djsearch-single-layout{
flex-direction:column;
align-items:center;
text-align:center;
}

.djsearch-single-image{
width:50%;
}

.djsearch-single-text{
text-align:center;
}

.djsearch-grid.two-results{
max-width:100%;
}

}


.djsearch-no-results{
text-align:center;
padding:20px;
margin-bottom:30px;
background:#f8f8f8;
border:1px solid #ddd;
border-radius:8px;
}
.djsearch-no-results h2{
margin:0;
font-size:24px;
}


.djsearch-top-match{margin:20px 0 40px;border-bottom:1px solid #ddd;padding-bottom:20px;}
.djsearch-top-match h2{text-align:center;margin-bottom:20px;}

</style>';

});
