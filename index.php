<?php

ini_set('memory_limit', '256M');

$productsPath = __DIR__ . "/products/";
$resultPath = __DIR__ . "/result/";
$codePath = __DIR__ . "/code/";

$files = array_diff(scandir($productsPath), ['..', '.']);

function getParameters($values) {
    global $products;
    global $parameters;
    global $naming;

    if (current($values)) {
        $key = key($values);
        $current = current($values);

        if (!empty($products)) {
            $productsInterim = [];
            foreach ($products as $product) {
                for ($i = 0; $i < count($current); $i++) {
                    $productsInterim[] = $product;

                    if ($current[$i] !== '-') {
                        $productsInterim[count($productsInterim) - 1]['code'] = $productsInterim[count($productsInterim) - 1]['code'] . '-' . $current[$i];
                    }

                    if (isset($parameters[$key])) {
                        $productsInterim[count($productsInterim) - 1]['parameters'][$parameters[$key]['name']]['value'] = $parameters[$key]['values'][$i];
                    }

                    if (isset($naming[$key])) {
                        $productsInterim[count($productsInterim) - 1]['parameters'][$parameters[$key]['name']]['description'] = $naming[$key][$i];
                    }
                }
            }

            $products = $productsInterim;
        } else {
            for ($i = 0; $i < count($current); $i++) {
                $products[]['code'] = $current[$i];
                $products[$i]['parameters'] = [];
            }
        }

        next($values);
        getParameters($values);
    }
}

foreach ($files as $file) {
    $fileinfo = pathinfo($file);
    $fileResource = fopen($productsPath . $file, 'r');

    $row = 0;
    $header = $position = $values = $parameters = $naming = $products = [];

    while ($data = fgetcsv($fileResource, separator: ';')) {
        if (!preg_match('#\##', $data[0]) and $row === 0) {
            throw new Exception("\n\n\tFirst column must be empty with heading \"#\"\n\n");
        }

        unset($data[0]);

        if ($row === 0) {
            $header = $data;

            foreach ($data as $columnKey => $columnData) {
                if (preg_match('#Параметр:(.*)#u', $columnData, $match)) {
                    [$parameter, $name] = explode(':', $match[1]);
                    $parameters += [$parameter => ['name' => $name, 'values' => []]];
                    $position['parameters'][] = $columnKey;

                } elseif (preg_match('#Условное обозначение:(.*)#u', $columnData, $match)) {
                    $position['naming'][] = $columnKey;

                } else {
                    $values += [$columnData => []];
                    $position['values'][] = $columnKey;
                }
            }
        } else {
            foreach ($data as $columnKey => $columnData) {
                if (!empty($columnData)) {
                    if (in_array($columnKey, $position['values'])) {
                        $values[$header[$columnKey]][] = $columnData;

                    } elseif (in_array($columnKey, $position['parameters'])) {
                        preg_match('#Параметр:(.*)#u', $header[$columnKey], $match);
                        [$parameter, $name] = explode(':', $match[1]);
                        $parameters[$parameter]['values'][] = $columnData;

                    } elseif (in_array($columnKey, $position['naming'])) {
                        preg_match('#Условное обозначение:(.*)#u', $header[$columnKey], $match);
                        $naming[$match[1]][] = $columnData;
                    }
                }
            }
        }

        $row++;
    }

    getParameters($values);
    file_put_contents($resultPath . $fileinfo['filename'] . ".json", json_encode($products));

    $codes = "";

    foreach ($products as $product) {
        $codes .= $product['code'] . "\n";
    }

    file_put_contents($codePath . $fileinfo['filename'] . ".txt", $codes, FILE_APPEND);
}