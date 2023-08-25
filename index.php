<?php

$productsPath = __DIR__ . "/products/";

$files = array_diff(scandir($productsPath), ['..', '.']);


foreach ($files as $file) {
    $fileResource = fopen($productsPath . $file, 'r');

    $row = 0;
    $header = [];

    while ($data = fgetcsv($fileResource, separator: ';')) {
        if (!preg_match('#\##', $data[0]) and $row === 0) {
            throw new Exception("\n\n\tFirst column must be empty with heading \"#\"\n\n");
        }

        if ($row === 0) {
            $header = $data;
        } else {
            var_dump($data);
            die();
        }

        $row++;
    }
}