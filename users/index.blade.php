@extends('layouts.app')

@section('content')
    <nav area-label="breadcrumb">

        <ol class="breadcrumb">
            <a href="{{ route('home') }}" class="text-dectoration-none mr-3">
                <li class="breadcrumb-item">Home</li>
            </a>
            <li class="breadcrumb-item active">Platform Users</li>
        </ol>

    </nav>
    <!-- Upload multiple users -->
    <div class="card mb-3">
        <div class="card-body">
            <form action="{{ route('upload.user.csv') }}" method="post" enctype="multipart/form-data">

                @csrf
                <div class="row">
                    <div class="form-group">
                        <label>Upload Excel/CSV Sheet</label>
                        <input type="file" name="file_csv" accept=".csv" id="file_csv"
                            class="form-control @error('file_csv') is-invalid @enderror">
                        @error('file_csv')
                            <div class="alert alert-danger mt-1" role="alert"><span>{{ $message }}</span>
                            </div>
                        @enderror
                    </div>
                    <div class="form-group mt-4 ml-5">
                        <button type="submit" class="btn btn-primary">Upload File</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="card">
        <div class="card-header">Platform Users</div>
        <div class="card-body">
            <table class="table table-dark table-bordered table-hover" id="users-table">
                <thead>
                    <th>#</th>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Contact Number</th>
                    <th>Contact Address</th>
                    <th>Billing Address</th>
                    <th>Action</th>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
@endsection
@section('scripts')
    <script>
        var table = $('#users-table').DataTable({
            autoWidth: false,
            order: [0, "ASC"],
            scrollX: true,
            processing: true,
            serverSide: true,
            searchDelay: 2000,
            paging: true,
            ajax: "{{ route('users.index') }}",
            columns: [{
                    data: 'id',
                    name: 'id',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'avatar',
                    name: 'avatar',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'name',
                    name: 'name'
                },
                {
                    data: 'email',
                    name: 'email'
                },
                {
                    data: 'contact_number',
                    name: 'contact_number'
                },
                {
                    data: 'contact_address',
                    name: 'contact_address'
                },
                {
                    data: 'address',
                    name: 'address'
                },
                {
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false
                }
            ]
        });
    </script>
@endsection
