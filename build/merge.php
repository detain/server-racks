<?php

require_once __DIR__.'/../../include/functions.inc.php';

$data = json_decode(file_get_contents('tripplite.json'), true);
$racks = [];
foreach ($data as $model => $row) {
    $rack = [
        'model' => $model,
        'vendor' => 'Tripp Lite',
        'title' => $row['title'],
        'color' => $row['specs']['PHYSICAL']['Color'],
        'units' => (int)str_replace('U', '', $row['specs']['PHYSICAL']['Rack Height']),
        'height' => 0,
        'width' => 0,
        'depth' => 0,
        'weight' => isset($row['specs']['PHYSICAL']['Unit Weight (lbs.)']) ? (float)$row['specs']['PHYSICAL']['Unit Weight (lbs.)'] : null,
        'max_mount_depth' => (float)$row['specs']['PHYSICAL']['Maximum Device Depth (in.)'],
        'perm_load' => (float)$row['specs']['PHYSICAL']['Weight Capacity - Stationary (lbs.)'],
        'images' => [],
        'files' => [],
    ];
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
    $rack['height'] = (float)$parts[0];
    $rack['width'] = (float)$parts[1];
    $rack['depth'] = (float)$parts[2];    
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
        'height' => preg_match('/^([\d\.]+) in/', $row['characteristic-tables']['Physical']['Height'], $matches) ? (float)$matches[1] : $row['characteristic-tables']['Physical']['Height'],
        'width' => preg_match('/^([\d\.]+) in/', $row['characteristic-tables']['Physical']['Width'], $matches) ? (float)$matches[1] : $row['characteristic-tables']['Physical']['Width'],
        'depth' => preg_match('/^([\d\.]+) in/', $row['characteristic-tables']['Physical']['Depth'], $matches) ? (float)$matches[1] : $row['characteristic-tables']['Physical']['Depth'],
        'weight' => preg_match('/^([\d\.]+) lb/', $row['characteristic-tables']['Physical']['Net Weight'], $matches) ? (float)$matches[1] : $row['characteristic-tables']['Physical']['Net Weight'],
        'max_mount_depth ' => isset($row['characteristic-tables']['Physical']['Maximum Mounting Depth']) ? (preg_match('/^([\d\.]+) in/', $row['characteristic-tables']['Physical']['Maximum Mounting Depth'], $matches) ? (float)$matches[1] : $row['characteristic-tables']['Physical']['Maximum Mounting Depth']) : null,
        'perm_load' => isset($row['characteristic-tables']['Physical']['Permanent permissible load']) ? (preg_match('/^([\d\.]+) lb/', $row['characteristic-tables']['Physical']['Permanent permissible load'], $matches) ? (float)$matches[1] : $row['characteristic-tables']['Physical']['Permanent permissible load']): null,
        'images' => [],
        'files' => [],
    ];
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
