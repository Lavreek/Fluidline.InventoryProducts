<?php

/**
 * SELECT msc.pagetitle, ptc.tmplvarid, ptd.value  FROM product_tmplvar_contentvalues AS ptc
 * LEFT JOIN product_tmplvar_data AS ptd
 * ON ptc.value = ptd.id
 * INNER JOIN modx_site_content AS msc
 * ON ptc.contentid = msc.id WHERE
 * `contentid` IN (SELECT `id` FROM `modx_site_content`
 * WHERE `parent` = 26128)
 */

$options = getopt("f:");

if (isset($options['f'])) {
    $filepath = $options['f'];
    $f = fopen($options['f'], 'r');

    $rowPosition = 0;

    $products = $headerValues = [];

    while ($data = fgetcsv($f, separator: ';')) {
        if ($rowPosition > 1) {
            if (!isset($products[$data[0]])) {
                $products[$data[0]] = [];
            }

            $products[$data[0]] += [$data[1] => $data[2]];

            if (!in_array($data[1], $headerValues)) {
                $headerValues[] = $data[1];
            }
        }

        $rowPosition++;
    }

    $data = "pagetitle,". implode(';', $headerValues) ."\n";

    foreach ($products as $productTitle => $productValues) {
        $data .= '"'. $productTitle .'";';

        foreach ($headerValues as $valuesIndex => $value) {
            if (isset($productValues[$value])) {
                $data .= '"'. str_replace(['"'], '', $productValues[$value]) .'"';
            }

            $data .= ";";
        }

        $data .= "\n";

    }

    file_put_contents(dirname($filepath) . "/revert.csv", $data);
}