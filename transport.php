<?php

/** Get parameters
 *
  SELECT msc.pagetitle, ptc.tmplvarid, ptd.value  FROM product_tmplvar_contentvalues AS ptc
  LEFT JOIN product_tmplvar_data AS ptd
  ON ptc.value = ptd.id
  INNER JOIN modx_site_content AS msc
  ON ptc.contentid = msc.id WHERE
  `contentid` IN (SELECT `id` FROM `modx_site_content`
  WHERE `parent` = 26128)
 */

/** Get price
 *
  SELECT msc.pagetitle, ptc.value FROM product_tmplvar_contentvalues AS ptc
  LEFT JOIN product_tmplvar_data AS ptd
  ON ptc.value = ptd.id
  INNER JOIN modx_site_content AS msc
  ON ptc.contentid = msc.id WHERE
  `contentid` IN (SELECT `id` FROM `modx_site_content`
  WHERE tmplvarid = 15 AND `parent` = 26128)
 */

ini_set('memory_limit', '1024M');

$options = getopt("f:");

if (isset($options['f'])) {
    $filepath = $options['f'];
    $f = fopen($options['f'], 'r');

    $rowPosition = 0;

    $products = $headerValues = [];

    $separator = ',';

    while ($data = fgetcsv($f, separator: $separator)) {
        if ($rowPosition < 1) {
            if (count($data) < 2) {
                preg_match('#\w+\"?([,|;])\"?\w+\"$#u', $data[0], $match);

                if (isset($match[1])) {
                    $separator = $match[1];
                }
            }
        }
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

    file_put_contents(dirname($filepath) ."/transport.csv", $data);
}
