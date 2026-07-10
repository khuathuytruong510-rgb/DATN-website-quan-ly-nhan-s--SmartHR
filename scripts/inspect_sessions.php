<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$sessions = DB::table('sessions')->get();

foreach ($sessions as $s) {
    echo "Session id: " . $s->id . PHP_EOL;
    // payload may be in 'payload' or 'payload' column depending on driver
    $payload = null;
    if (isset($s->payload)) {
        $payload = $s->payload;
    } elseif (isset($s->data)) {
        $payload = $s->data;
    }

    if (! $payload) {
        echo " - no payload\n";
        continue;
    }

    // try to json_decode
    $decoded = json_decode($payload, true);
    if (! $decoded) {
        // maybe base64 serialized
        echo " - payload not json\n";
        continue;
    }

    $imp = $decoded['impersonator_id'] ?? null;
    $uid = $decoded['login_web_59ba36addc2b2f9401580f014c7f58ea4e30989d'] ?? ($decoded['_token'] ?? null);
    echo " - impersonator_id: " . ($imp ?? 'NULL') . PHP_EOL;
    echo " - raw keys: " . implode(',', array_keys($decoded)) . PHP_EOL;
}

