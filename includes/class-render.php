<?php
if(!defined('ABSPATH')) exit;
class DJSearch_Render{
 public static function output($html){return $html;}
 public static function search_header($search_query){ob_start();include dirname(__DIR__).'/templates/search-header.php';return ob_get_clean();}
 public static function no_results($search_query=''){ob_start();include dirname(__DIR__).'/templates/no-results.php';return ob_get_clean();}
}
