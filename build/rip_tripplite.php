<?php

require_once __DIR__.'/../include/functions.inc.php';


use PHPCore\SimpleHtmlDom\HtmlDocument;

$html = new HtmlDocument();

$items = [];
foreach (glob(__DIR__.'/data/tripplite/'.'*.html') as $file) {
	$html->load(file_get_contents($file));
    $item = [
        'title' => trim($html->find('.model-seo', 0)->innertext),
        'model' => trim($html->find('#model-number .fw700', 0)->innertext),
        'sections' => [],
        'specs' => [],
        'features' => [],
        'benefits' => [],
        'description' => [],
        'images' => [],
    ];
    foreach ($html->find('.resources>.resourceBlock') as $block) {
	    $section = trim($block->find('p', 0)->innertext);
        if ($section == 'Product Images') {
            continue;
        }
        $item['sections'][$section] = [];
	    foreach ($block->find('ul>li>a') as $link) {
            $linkUrl = trim($link->href);
            $linkText = trim($link->innertext);
            $item['sections'][$section][$linkUrl] = $linkText;
        }
    }
    $searchString = 'Rack Height';
    foreach ($html->find('.specContainer .specGroup') as $block) {
	    $section = trim($block->find('h5 strong', 0)->innertext);
        $item['specs'][$section] = [];
	    foreach ($block->find('table>tbody>tr') as $row) {
		    $field = trim($row->find('.specName', 0)->innertext);
            if (substr($field, 0, strlen($searchString))== $searchString) {
                $field = $searchString;
            }
            $value = trim($row->find('.specValue', 0)->innertext);
            $item['specs'][$section][$field] = $value;        
	    }
    }
    foreach ($html->find('.feature>p') as $block) {
        $text = trim($block->innertext);
        if (preg_match('/^<strong>(.*)<\/strong><br\/>(.*)$/', $text, $matches)) {
            $field = $matches[1];
            $text = $matches[2];
            $item['features'][$field] = $text;
        } else {
            $item['features'][] = $text;
        }
    }
    foreach ($html->find('.key-benefits>p') as $block) {
	    $item['benefits'][] = trim($block->innertext);
    }
    foreach ($html->find('.description-bullets>ul>li') as $block) {
	    $item['description'][] = trim($block->innertext);
    }
    foreach ($html->find('#hidden-thumbnail-carousel>a') as $block) {
        $var = 'data-src';
        $linkUrl = trim($block->$var);
        $linkText = trim($block->find('img', 0)->alt);
        $item['images'][$linkUrl] = trim($linkText);
    }
    $items[$item['model']] = $item;
}
file_put_contents('tripplite.json', json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));