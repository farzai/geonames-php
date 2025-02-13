<?php

// Ensure we have valid test data files
if (! file_exists(__DIR__.'/postal_codes.txt')) {
    file_put_contents(__DIR__.'/postal_codes.txt',
        "TH\t10200\tBang Rak\tBangkok\t10\t\t\t13.7235\t100.5147\t1\n".
        "TH\t10330\tWattana\tBangkok\t10\t\t\t13.7333\t100.5667\t1\n".
        "TH\t50000\tMueang Chiang Mai\tChiang Mai\t41\t\t\t18.7904\t98.9847\t1\n"
    );
}

if (! file_exists(__DIR__.'/gazetteer.txt')) {
    file_put_contents(__DIR__.'/gazetteer.txt',
        "1609350\tBangkok\tBangkok\tKrung Thep,กรุงเทพมหานคร\t13.75\t100.51667\tP\tPPLC\tTH\t\t40\t\t01\t\t\t5104476\t2\t4\tAsia/Bangkok\t2023-01-12\n".
        "1153671\tChiang Mai\tChiang Mai\tเชียงใหม่\t18.79038\t98.98468\tP\tPPLA\tTH\t\t41\t\t01\t\t\t131091\t310\t309\tAsia/Bangkok\t2023-01-12\n".
        "1151254\tPhuket\tPhuket\tภูเก็ต\t7.89059\t98.3981\tP\tPPLA\tTH\t\t42\t\t\t\t\t77612\t5\t5\tAsia/Bangkok\t2023-01-12\n"
    );
}

// Create postal codes ZIP
$zip = new ZipArchive;
if ($zip->open(__DIR__.'/TH.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
    $zip->addFromString('TH.txt', file_get_contents(__DIR__.'/postal_codes.txt'));
    $zip->close();
} else {
    throw new RuntimeException('Failed to create postal codes ZIP file');
}

// Create gazetteer ZIP
$zip = new ZipArchive;
if ($zip->open(__DIR__.'/TH_gaz.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
    $zip->addFromString('TH.txt', file_get_contents(__DIR__.'/gazetteer.txt'));
    $zip->close();
} else {
    throw new RuntimeException('Failed to create gazetteer ZIP file');
}

echo "Test data created successfully.\n";
