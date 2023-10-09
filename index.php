<?php

ini_set('memory_limit', '1024M');

$productsPath = __DIR__ . "/inventory/Клапаны/";
$resultPath = __DIR__ . "/result/";
$codePath = __DIR__ . "/code/";

//$files = array_diff(scandir($productsPath), ['..', '.', 'good']);
$files = ["105.csv"];

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
                        $productsInterim[count($productsInterim) - 1]['code'] =
                            $productsInterim[count($productsInterim) - 1]['code'] . '-' . $current[$i];
                    }

                    if (isset($parameters[$key])) {
                        $description = [];

                        if (isset($naming[$key])) {
                            foreach ($naming[$key] as $itemKey => $item) {
                                if (isset($naming[$key][$itemKey][$i])) {
                                    $description[$itemKey] = $naming[$key][$itemKey][$i];
                                }
                            }
                        }

                        foreach ($parameters[$key] as $groupKey => $groupValue) {
                            if (!isset($parameters[$key][$groupKey][$i])) {
                                $group = [
                                    'name' => $groupKey,
                                    'value' => "",
                                ];
                            } else {
                                $group = [
                                    'name' => $groupKey,
                                    'value' => trim($parameters[$key][$groupKey][$i], "\""),
                                ];

                                if (isset($description[$groupKey])) {
                                    $group['description'] = $description[$groupKey];
                                }
                            }

                            $productsInterim[count($productsInterim) - 1]['parameters'][] = $group;
                        }
                    }
                }
            }

            $products = $productsInterim;
        } else {
            for ($i = 0; $i < count($current); $i++) {
                $products[$i]['code'] = $current[$i];
                $products[$i]['parameters'] = [];

                if (isset($parameters[$key])) {
                    $description = [];

                    if (isset($naming[$key])) {
                        foreach ($naming[$key] as $itemKey => $item) {
                            if (isset($naming[$key][$itemKey][$i])) {
                                $description[$itemKey] = $naming[$key][$itemKey][$i];
                            }
                        }
                    }

                    foreach ($parameters[$key] as $groupKey => $groupValue) {
                        $group = [
                            'name' => $groupKey,
                            'value' => trim($parameters[$key][$groupKey][$i], "\""),
                        ];

                        if (isset($description[$groupKey])) {
                            $group['description'] = $description[$groupKey];
                        }

                        $products[$i]['parameters'][] = $group;
                    }
                }
            }
            var_dump($products);
            die();
        }

        next($values);
        getParameters($values);
    }
}

foreach ($files as $file) {
    $fileinfo = pathinfo($file);
    $fileResource = fopen($productsPath . $file, 'r');

    $row = 0;
    $header = $position = $values = $parameters = $naming = $products = $conditions = [];

    $delimiter = false;
    $tries = 0;

    while (!$delimiter) {
        $prev = stream_get_contents($fileResource,1);

        if ($prev == '#') {
            $delimiter = stream_get_contents($fileResource,1);
        }

        if ($tries > 10) {
            return [];
        }

        $tries++;
    }

    rewind($fileResource);

    while ($data = fgetcsv($fileResource, separator: $delimiter)) {
        if (!preg_match('#\##', $data[0]) and $row === 0) {
            throw new \Exception("\n\n\tFirst column must be empty with heading \"#\"\n\n");
        }

        unset($data[0]);

        if ($row === 0) {
            $header = $data;

            foreach ($data as $columnKey => $columnData) {
                $columnKey = trim($columnKey, "\"");
                $columnData = trim($columnData, "\"");

                if (preg_match('#Параметр:(.*)#u', $columnData, $match)) {
                    [$parameter, $name] = explode(':', $match[1]);
                    $parameters[$parameter][$name] = [];
                    $position['parameters'][] = $columnKey;

                } elseif (preg_match('#Условное обозначение:(.*)#u', $columnData, $match)) {
                    $position['naming'][] = $columnKey;

                } elseif (
                    preg_match('#Условие:(.*)#u', $columnData, $match) ||
                    preg_match('#ЕСЛИ\((.+)\)=\((.+)\)#u', $columnData, $match)
                ) {
                    $position['conditions'][] = $columnKey;
                    $conditions[$match[1]] = [];

                } else { // Добавление основного порядка параметров продукции
                    $values += [$columnData => []];
                    $position['values'][] = $columnKey;
                }
            }
        } else {
            foreach ($data as $columnKey => $columnData) {
                if (!empty($columnData)) {
                    $columnKey = trim($columnKey, "\"");
                    $columnData = trim($columnData, "\"");

                    if (in_array($columnKey, $position['values'])) {
                        $values[$header[$columnKey]][] = $columnData;

                    } elseif (in_array($columnKey, $position['parameters'])) {
                        preg_match('#Параметр:(.*)#u', $header[$columnKey], $match);
                        [$parameter, $name] = explode(':', $match[1], 2);
                        $parameters[$parameter][$name][] = $columnData;

                    } elseif (in_array($columnKey, $position['naming'])) {
                        preg_match('#Условное обозначение:(.*)#u', $header[$columnKey], $match);
                        [$columnName, $columnTarget] = explode(":", $match[1], 2);
                        $naming[$columnName][$columnTarget][] = $columnData;

                    } elseif (in_array($columnKey, $position['conditions'])) {
                        preg_match('#ЕСЛИ\((.+)\)=\((.+)\)#u', $columnData, $match);
                        [$conditionKey, $conditionValue] = explode(":", $header[$columnKey]);

                        if (isset($match[0])) {
                            $conditionsExplode = explode('&', $match[1]);

                            foreach ($conditionsExplode as $exp) {
                                $conditions[$conditionValue][] = $exp;
                            }

                            var_dump($conditions);
                            die('here');
                        }
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

//    file_put_contents($codePath . $fileinfo['filename'] . ".txt", $codes, FILE_APPEND);
}