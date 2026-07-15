<?php
require __DIR__ . '/../vendor/autoload.php';
use Carbon\Carbon;

$checkIn = Carbon::parse('2026-07-15 08:13:00');
$checkOut = Carbon::parse('2026-07-15 17:09:00');
$standardCheckIn = Carbon::parse('2026-07-15 08:00:00');
$standardCheckOut = Carbon::parse('2026-07-15 17:30:00');

$totalMinutes = $checkOut->diffInMinutes($checkIn);
$totalMinutesFalse = $checkOut->diffInMinutes($checkIn, false);
$late = $checkIn->diffInMinutes($standardCheckIn);
early = $standardCheckOut->diffInMinutes($checkOut);
overtime = $checkOut->diffInMinutes($standardCheckOut);

echo "totalMinutes={$totalMinutes}\n";
echo "totalMinutesFalse={$totalMinutesFalse}\n";
echo "late={$late}\n";
echo "early={$early}\n";
echo "overtime={$overtime}\n";
