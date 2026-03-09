@extends('layouts.dashboard')

@section('title', 'Users')

@section('content')
@if(session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

<div class="card"><div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="card-title mb-0">Users</h6>
        @can('users.create')
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">Add User</button>
        @endcan
    </div>

    <div class="table-responsive">
        <table class="table table-hover w-100 align-middle">
            <thead><tr><th>Name</th><th>Email</th><th>Roles</th><th>Direct Permissions</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            @forelse($users as $user)
                <tr>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>@forelse($user->roles as $role)<span class="badge bg-primary me-1">{{ $role->name }}</span>@empty<span class="text-muted">No role</span>@endforelse</td>
                    <td>@forelse($user->permissions as $permission)<span class="badge bg-secondary me-1">{{ $permission->name }}</span>@empty<span class="text-muted">Inherited only</span>@endforelse</td>
                    <td class="text-end">
                        @can('users.update')
                            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editUserModal{{ $user->id }}">Edit</button>
                        @endcan
                        @can('users.delete')
                            @if(auth()->id() !== $user->id)
                                <form method="POST" action="{{ route('app.users.destroy', $user) }}" class="d-inline">@csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete user?')">Delete</button>
                                </form>
                            @endif
                        @endcan
                    </td>
                </tr>

                @can('users.update')
                <div class="modal fade" id="editUserModal{{ $user->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-xl modal-dialog-centered"><div class="modal-content">
                        <form method="POST" action="{{ route('app.users.update', $user) }}">@csrf @method('PATCH')
                            <div class="modal-header"><h5 class="modal-title">Edit User: {{ $user->name }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-4 mb-3"><label class="form-label">Name</label><input name="name" class="form-control" value="{{ $user->name }}" required></div>
                                    <div class="col-md-4 mb-3"><label class="form-label">Email</label><input name="email" type="email" class="form-control" value="{{ $user->email }}" required></div>
                                    <div class="col-md-4 mb-3"><label class="form-label">Password (optional)</label><input name="password" type="password" class="form-control"></div>
                                </div>

                                @can('users.update_roles_permissions')
                                    <div class="row">
                                        <div class="col-md-4">
                                            <h6 class="mb-2">Roles</h6>
                                            @foreach($roles as $role)
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" name="roles[]" value="{{ $role->name }}" id="u{{ $user->id }}r{{ $role->id }}" {{ $user->hasRole($role->name) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="u{{ $user->id }}r{{ $role->id }}">{{ $role->name }}</label>
                                                </div>
                                            @endforeach
                                        </div>
                                        <div class="col-md-8">
                                            <h6 class="mb-2">Direct Permissions</h6>
                                            <div class="row">
                                                @foreach($permissions as $permission)
                                                    <div class="col-md-6">
                                                        <div class="form-check mb-2">
                                                            <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $permission->name }}" id="u{{ $user->id }}p{{ $permission->id }}" {{ $user->hasDirectPermission($permission->name) ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="u{{ $user->id }}p{{ $permission->id }}">{{ $permission->name }}</label>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @endcan
                            </div>
                            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Save</button></div>
                        </form>
                    </div></div>
                </div>
                @endcan
            @empty
                <tr><td colspan="5" class="text-muted">No users found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{ $users->links() }}
</div></div>

@can('users.create')
<div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered"><div class="modal-content">
        <form method="POST" action="{{ route('app.users.store') }}">@csrf
            <div class="modal-header"><h5 class="modal-title">Add User</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-4 mb-3"><label class="form-label">Name</label><input name="name" class="form-control" required></div>
                    <div class="col-md-4 mb-3"><label class="form-label">Email</label><input name="email" type="email" class="form-control" required></div>
                    <div class="col-md-4 mb-3"><label class="form-label">Password</label><input name="password" type="password" class="form-control" required></div>
                </div>

                @can('users.update_roles_permissions')
                    <div class="row">
                        <div class="col-md-4">
                            <h6 class="mb-2">Roles</h6>
                            @foreach($roles as $role)
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="roles[]" value="{{ $role->name }}" id="newu-role-{{ $role->id }}">
                                    <label class="form-check-label" for="newu-role-{{ $role->id }}">{{ $role->name }}</label>
                                </div>
                            @endforeach
                        </div>
                        <div class="col-md-8">
                            <h6 class="mb-2">Direct Permissions</h6>
                            <div class="row">
                                @foreach($permissions as $permission)
                                    <div class="col-md-6">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $permission->name }}" id="newu-permission-{{ $permission->id }}">
                                            <label class="form-check-label" for="newu-permission-{{ $permission->id }}">{{ $permission->name }}</label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endcan
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Create User</button></div>
        </form>
    </div></div>
</div>
@endcan
@endsection
