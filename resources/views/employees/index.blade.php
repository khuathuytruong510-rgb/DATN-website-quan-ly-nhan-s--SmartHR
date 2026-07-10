@extends('layouts.app')

@section('content')
<employee-manager
    :initial-employees='@json($employees->items(), JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG)'
    @submit="handleSubmit"
    @delete="handleDelete"
></employee-manager>
@endsection

@push('scripts')
<script>
// Handle employee form submission
const handleSubmit = async (data) => {
    const url = data.id ? `/employees/${data.id}` : '/employees';
    const method = data.id ? 'PUT' : 'POST';
    
    const response = await fetch(url, {
        method,
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify(data)
    });
    
    if (response.ok) {
        window.location.reload();
    }
};

// Handle employee deletion
const handleDelete = async (id) => {
    const response = await fetch(`/employees/${id}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    });
    
    if (response.ok) {
        window.location.reload();
    }
};

// Make functions available to Vue component via window
window.handleSubmit = handleSubmit;
window.handleDelete = handleDelete;
</script>
@endpush
