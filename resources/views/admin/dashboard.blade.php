@extends('layouts.app')

@section('title', 'Dashboard - SmartHR')

@section('content')
    @php
        $dashboardStats = [
            'departments'       => $hrOverview['totalDepartments'] ?? 0,
            'employees'         => $hrOverview['totalEmployees'] ?? 0,
            'contracts'         => $contractStats['total'] ?? 0,
            'latestEmployees'   => [],
            'latestContracts'   => [],
            'expiringContracts' => $expiringContracts ?? [],
        ];
    @endphp

    <dashboard
        :stats='@json($dashboardStats, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG)'
    ></dashboard>
@endsection
