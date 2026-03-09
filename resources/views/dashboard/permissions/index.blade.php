@extends('layouts.dashboard')

@section('title', 'Permissions')

@section('content')
@if(session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

<div class="card"><div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="card-title mb-0">Permissions</h6>
        @can('permissions.create')<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPermissionModal">Add Permission</button>@endcan
    </div>

    <div class="table-responsive">
        <table class="table table-hover w-100">
            <thead><tr><th>Name</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            @forelse($permissions as $permission)
                <tr>
                    <td>{{ $permission->name }}</td>
                    <td class="text-end">
                        @can('permissions.update')<button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editPermissionModal{{ $permission->id }}">Edit</button>@endcan
                        @can('permissions.delete')
                            <form method="POST" action="{{ route('app.permissions.destroy', $permission) }}" class="d-inline">@csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete permission?')">Delete</button>
                            </form>
                        @endcan
                    </td>
                </tr>

                @can('permissions.update')
                <div class="modal fade" id="editPermissionModal{{ $permission->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-md modal-dialog-centered"><div class="modal-content">
                        <form method="POST" action="{{ route('app.permissions.update', $permission) }}">@csrf @method('PATCH')
                            <div class="modal-header"><h5 class="modal-title">Edit Permission</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                            <div class="modal-body"><label class="form-label">Name</label><input name="name" class="form-control" value="{{ $permission->name }}" required></div>
                            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Save</button></div>
                        </form>
                    </div></div>
                </div>
                @endcan
            @empty
                <tr><td colspan="2" class="text-muted">No permissions found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{ $permissions->links() }}
</div></div>

@can('permissions.create')
<div class="modal fade" id="addPermissionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered"><div class="modal-content">
        <form method="POST" action="{{ route('app.permissions.store') }}">@csrf
            <div class="modal-header"><h5 class="modal-title">Add Permission</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body"><label class="form-label">Name</label><input name="name" class="form-control" required></div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Create</button></div>
        </form>
    </div></div>
</div>
@endcan
@endsection
