@extends('layouts.dashboard')

@section('title', 'Roles')

@section('content')
@if(session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

<div class="card"><div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="card-title mb-0">Roles</h6>
        @can('roles.create')<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRoleModal">Add Role</button>@endcan
    </div>

    <div class="table-responsive">
        <table class="table table-hover w-100">
            <thead><tr><th>Name</th><th>Permissions</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            @forelse($roles as $role)
                <tr>
                    <td>{{ $role->name }}</td>
                    <td>@forelse($role->permissions as $permission)<span class="badge bg-secondary me-1">{{ $permission->name }}</span>@empty<span class="text-muted">No permissions</span>@endforelse</td>
                    <td class="text-end">
                        @can('roles.update')<button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editRoleModal{{ $role->id }}">Edit</button>@endcan
                        @can('roles.delete')
                            <form method="POST" action="{{ route('app.roles.destroy', $role) }}" class="d-inline">@csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete role?')">Delete</button>
                            </form>
                        @endcan
                    </td>
                </tr>

                @can('roles.update')
                <div class="modal fade" id="editRoleModal{{ $role->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-xl modal-dialog-centered"><div class="modal-content">
                        <form method="POST" action="{{ route('app.roles.update', $role) }}">@csrf @method('PATCH')
                            <div class="modal-header"><h5 class="modal-title">Edit Role</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                            <div class="modal-body">
                                <div class="mb-3"><label class="form-label">Name</label><input name="name" class="form-control" value="{{ $role->name }}" required></div>
                                <div class="row">
                                    @foreach($permissions as $permission)
                                        <div class="col-md-4"><div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $permission->name }}" id="r{{ $role->id }}p{{ $permission->id }}" {{ $role->hasPermissionTo($permission->name) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="r{{ $role->id }}p{{ $permission->id }}">{{ $permission->name }}</label>
                                        </div></div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Save</button></div>
                        </form>
                    </div></div>
                </div>
                @endcan
            @empty
                <tr><td colspan="3" class="text-muted">No roles found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{ $roles->links() }}
</div></div>

@can('roles.create')
<div class="modal fade" id="addRoleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered"><div class="modal-content">
        <form method="POST" action="{{ route('app.roles.store') }}">@csrf
            <div class="modal-header"><h5 class="modal-title">Add Role</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label">Name</label><input name="name" class="form-control" required></div>
                <div class="row">
                    @foreach($permissions as $permission)
                        <div class="col-md-4"><div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $permission->name }}" id="newp{{ $permission->id }}">
                            <label class="form-check-label" for="newp{{ $permission->id }}">{{ $permission->name }}</label>
                        </div></div>
                    @endforeach
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Create</button></div>
        </form>
    </div></div>
</div>
@endcan
@endsection
