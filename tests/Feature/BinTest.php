<?php

// The CLI script itself, run as a user runs it. The command tests construct
// the commands directly, so they never see how bin/geonames registers them.
it('lists both download commands', function () {
    $output = [];
    $code = 0;
    exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg(__DIR__.'/../../bin/geonames').' list --no-ansi 2>&1', $output, $code);
    $text = implode("\n", $output);

    expect($code)->toBe(0, $text);
    expect($text)->toContain('geonames:download');
    expect($text)->toContain('geonames:gazetteer:download');
});
