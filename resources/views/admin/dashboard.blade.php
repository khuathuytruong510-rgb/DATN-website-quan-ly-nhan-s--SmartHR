@extends('layouts.app')

@section('title', 'Dashboard - SmartHR')

@section('content')
    @php
        $dashboardStats = [
            'departments' => $departmentCount,
            'employees' => $employeeCount,
            'contracts' => $contractCount,
            'latestEmployees' => $latestEmployees,
            'latestContracts' => $latestContracts,
            'expiringContracts' => $expiringContracts,
        ];
    @endphp

    <div
        id="vue-dashboard"
        data-stats='@json($dashboardStats, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG)'
    ></div>
@endsection
