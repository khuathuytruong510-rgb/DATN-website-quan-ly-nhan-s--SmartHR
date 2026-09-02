<?php

return [
    'office_latitude' => (float) env('ATTENDANCE_OFFICE_LAT', 21.0285),
    'office_longitude' => (float) env('ATTENDANCE_OFFICE_LNG', 105.8542),
    'allowed_distance_meters' => (float) env('ATTENDANCE_RADIUS_METERS', 60),
    'face_match_distance' => (float) env('ATTENDANCE_FACE_DISTANCE', 0.55),
];
