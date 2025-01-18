\<?php

require_once __DIR__.'/../../include/functions.inc.php';

$data = json_decode(file_get_contents('tripplite.json'), true);
$racks = [];
foreach ($data as $model => $row) {
    $rack = [
        'model' => $model,
        'vendor' => 'Tripp Lite',
        'title' => str_replace([' '.$row['specs']['PHYSICAL']['Rack Height'], ', '.$row['specs']['PHYSICAL']['Color'], ', TAA'], ['', '', ''], $row['title']),
        'color' => $row['specs']['PHYSICAL']['Color'],
        'units' => (int)str_replace('U', '', $row['specs']['PHYSICAL']['Rack Height']),
        'height' => 0,
        'width' => 0,
        'depth' => 0,
        'images' => [],
        'files' => [],
    ];
    $rack['title'] = str_replace([' - Wide,', ' Standard-Depth', ' Extra-Deep', ' Extra-Wide', ' Deep and Wide', ' Mid-Depth', ' Shallow-Depth', 'SmartRack Wide'], [',', '', '', '', '', '', '', 'SmartRack'], $rack['title']);
    $rack['title'] = preg_replace('/ - \d+ in. Depth,/i', ',', $rack['title']);
    $rack['title'] = preg_replace('/ - \d+ in. \(\d+ mm\) Depth,/i', ',', $rack['title']);
    $rack['title'] = preg_replace('/, \d+ in. Depth,/i', ',', $rack['title']);
    if (isset($row['sections']['Design Resources'])) {
        foreach ($row['sections']['Design Resources'] as $url => $title) {
            if (strpos($title, 'BIM Object') !== false) {
                $rack['files'][$url] = $title;
            }
        }
    }
    foreach ($row['images'] as $url => $title) {
	if ($url == '') {
            continue;
        }
        $base = basename($url);
	if (substr($url, 0, 1) == '/') {
		$url = 'https:'.$url;
	}
        $file = 'tripplite_'.$base;
        if (!file_exists(__DIR__.'/../images/'.$file)) {
            $cmd = "wget -O '../images/{$file}' '{$url}'";
            echo `$cmd;`;
        }
        $rack['images'][$file] = str_replace([' thumbnail image', "Image shown is a representation only. Standard-width enclosures do not have wide mounting rails."], ['', $model], $title);
    }
    $parts = explode(' x ', $row['specs']['PHYSICAL']['Unit Dimensions (hwd / in.)']);
    $rack['height'] = (float)round($parts[0], 1);
    $rack['width'] = (float)round($parts[1], 1);
    $rack['depth'] = (float)round($parts[2], 1);
    $racks[] = $rack;
}
$data = json_decode(file_get_contents('apc.json'), true);
foreach ($data as $model => $row) {
    $rack = [
        'model' => $model,
        'vendor' => 'APC',
        'title' => str_replace('[TAA, BAA_COTS] ', '', $row['product-info']['description']),
        'units' => (int)str_replace('U', '', isset($row['characteristic-tables']['Main']['Number of rack unit']) ? $row['characteristic-tables']['Main']['Number of rack unit'] : $row['characteristic-tables']['Physical']['Number of rack unit']),
        'color' => isset($row['characteristic-tables']['Physical']['Color']) ? $row['characteristic-tables']['Physical']['Color'] : (in_array($model, ['AR3187B2', 'MDC42SX5KVAT']) ? 'Black' : 'White'),
        'height' => preg_match('/^([\d\.]+) in/', $row['characteristic-tables']['Physical']['Height'], $matches) ? (float)round($matches[1], 1) : $row['characteristic-tables']['Physical']['Height'],
        'width' => preg_match('/^([\d\.]+) in/', $row['characteristic-tables']['Physical']['Width'], $matches) ? (float)round($matches[1], 1) : $row['characteristic-tables']['Physical']['Width'],
        'depth' => preg_match('/^([\d\.]+) in/', $row['characteristic-tables']['Physical']['Depth'], $matches) ? (float)round($matches[1], 1) : $row['characteristic-tables']['Physical']['Depth'],
        'images' => [],
        'files' => [],
    ];
    $rack['title'] = str_replace([', '.$rack['units'].'U', ' '.$rack['units'].'U', ', '.$rack['color'], ' '.$rack['color']], ['', '', '', ''], $rack['title']);
    $rack['title'] = preg_replace('/, \d+H x \d+W x \d+D mm/i', '', $rack['title']);
    $rack['title'] = preg_replace('/ \d+mm x \d+mm /i', '', $rack['title']);
    $rack['title'] = preg_replace('/, \d+ lbs/i', '', $rack['title']);
    $idx = 0;
    foreach ($row['images'] as $url => $title) {
        $idx++;
        $file = 'apc_'.strtolower($model).'_'.$idx.'.jpg';
        if (!file_exists(__DIR__.'/../images/'.$file)) {
            $cmd = "wget -O '../images/{$file}' '{$url}'";
            echo `$cmd;`;
        }
        $rack['images'][$file] = 'APC '.$model;
    }
    foreach ($row['documents'] as $url => $docData) {
        if (preg_match('/\.(rfa|vss|dwg)$/i', $docData['file'])) {
            $rack['files'][$fileUrl] = $docData['title']; 
        }
        if (isset($docData['files'])) {
            foreach ($docData['files'] as $fileUrl => $fileName) {
                if (preg_match('/\.(rfa|vss|dwg)$/i', $fileName)) {
                    $rack['files'][$fileUrl] = $docData['title']; 
                }
            }
        }
    }
    $racks[] = $rack;
}
file_put_contents('../racks.json', json_encode($racks, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
