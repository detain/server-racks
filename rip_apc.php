<?php

require_once __DIR__.'/../include/functions.inc.php';


use PHPCore\SimpleHtmlDom\HtmlDocument;

$html = new HtmlDocument();
/*

const pesOb
*/ 
$items = [];
$elements = ['pes-description-and-specifications','pes-asset-bar-and-related-products','pes-product-main'];
foreach (glob(__DIR__.'/data/apc/'.'*.html') as $file) {
	$html->load(file_get_contents($file));
    $item = [];
    foreach ($elements as $element) {
        $elem = $html->find($element, 0);
        $attribs = $elem->getAllAttributes();
        foreach ($attribs as $attrib => $data) {
            if (substr($attrib, 0, 6) == 'plain-') {
                $attrib = substr($attrib, 6);
                if (in_array(substr($data, 0, 1), ['[', '{'])) {
                    $json = json_decode($data, true);
                    if ($json !== false) {
                        $data = $json;
                        if ($attrib == "characteristic-tables") {
                            $data = [];
                            foreach ($json as $tableIdx => $tableData) {
                                $tableName = $tableData['tableName'];
                                $rows = [];
                                foreach ($tableData['rows'] as $rowIdx => $rowData) {
                                    $field = $rowData["characteristicName"];
                                    $value = $rowData["characteristicValues"][0]['labelText'];
                                    $rows[$field] = $value;
                                }
                                $data[$tableName] = $rows;
                            }
                        } elseif ($attrib == "asset-bar") {
                            $attrib = "documents";
                            $docs = [];
                            foreach ($data['documents'] as $docData) {
                                $docs[] = [
                                    'url' => $docData['url'],
                                    'type' => $docData['documentType'],
                                    'file' => $docData['documentName'],
                                    'title' => $docData['titleForDisplay'],
                                ];
                            }
                            $data = $docs;
                        } elseif ($attrib == "product-media") {
                            $images = [];
                            $imageUrl = $data["zoomPictureDesktop"]['url'];
                            $imageName = $data["zoomPictureDesktop"]['title'];
                            $images[$imageUrl] = $imageName;
                            if (count($data["alternativeImages"]) > 0) {
                                foreach ($data["alternativeImages"] as $imageIdx => $imageData) {
                                    $imageUrl = $imageData['desktop']['url'];
                                    $imageName = $imageData['desktop']['title'];
                                    $images[$imageUrl] = $imageName;
                                }
                            }
                            $attrib = "images";
                            $data = $images;
                        } elseif (in_array($attrib, ["product-id", "product-info"])) {
                            
                        } else {
                            continue;
                        }
                    }
                } else {
                    if (!in_array($attrib, ["product-id"])) {
                        continue;
                    }
                }
                $item[$attrib] = $data; 
            } else {
                continue;
            }
        }
    }
    $items[$item["product-id"]] = $item;
}
file_put_contents('apc.json', json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
