<?php
require __DIR__ . '/../vendor/autoload.php';
use Carbon\Carbon;

$checkIn = Carbon::parse('2026-06-10 08:13:00');
$checkOut = Carbon::parse('2026-06-10 17:09:00');

echo 'checkIn=' . $checkIn->toDateTimeString() . "\n";
echo 'checkOut=' . $checkOut->toDateTimeString() . "\n";
echo 'diffInMinutes=' . $checkOut->diffInMinutes($checkIn) . "\n";
echo 'diffInMinutesFalse=' . $checkOut->diffInMinutes($checkIn, false) . "\n";
$breakStart = Carbon::parse('2026-06-10 12:00:00');
$breakEnd = Carbon::parse('2026-06-10 13:30:00');
$effectiveStart = $checkIn->greaterThan($breakStart) ? $checkIn : $breakStart;
$effectiveEnd = $checkOut->lessThan($breakEnd) ? $checkOut : $breakEnd;
echo 'breakMinutes=' . max(0, $effectiveEnd->diffInMinutes($effectiveStart)) . "\n";
