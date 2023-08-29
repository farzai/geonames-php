<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Farzai\Geonames\Client;

// Create a new client
$client = new Client();

// Get all countries and return $resource
$resource = $client->getCountryInfo();

$countries = $resource->asArray();
?>

<!doctype html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body>
    
    <div class="mt-4 p-4">
        <h1 class="text-2xl font-bold">
            Get all countries
        </h1>

        <?php
            if (count($countries) > 0) {
                $headers = array_keys($countries[0]);
            ?>

            <table class="mt-4 w-full border-collapse border border-gray-400">
                <thead class="bg-gray-200">
                    <tr>
                        <?php foreach ($headers as $header) { ?>
                            <th class="border border-gray-400 p-2"><?= $header ?></th>
                        <?php } ?>
                    </tr>
                </thead>

                <tbody class="bg-white">
                    <?php foreach ($countries as $country) { ?>
                        <tr class="border border-gray-400 odd:bg-gray-100">
                            <?php foreach ($country as $value) { ?>
                                <td class="border border-gray-400 p-2"><?= $value ?></td>
                            <?php } ?>
                        </tr>
                    <?php } ?>
                </tbody>

            </table>

        <?php } else { ?>
            <p>No countries found</p>
        <?php } ?>
    </div>
        
</body>
</html>