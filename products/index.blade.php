@extends('layouts.app')

@section('content')
    <!-- breadcrumb -->
    <nav area-label="breadcrumb">

        <ol class="breadcrumb">
            <a href="{{ route('home') }}" class="text-decoration-none mr-3">
                <li class="breadcrumb-item">Home</li>
            </a>
            <li class="breadcrumb-item active">Products</li>
        </ol>

    </nav>
    <!-- Upload multiple products -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">

                    <form action="{{ route('upload.excel') }}" method="post" enctype="multipart/form-data">

                        @csrf
                        <div class="form-group">
                            <label>Upload Excel / CSV</label>
                            <input type="file" name="file" id="file"
                                class="form-control @error('date_reported') is-invalid @enderror">
                            @error('file')
                                <div class="alert alert-danger mt-1" role="alert"><span>{{ $message }}</span>
                                </div>
                            @enderror
                        </div>
                        <div class="form-group mt-4 ml-5">
                            <button type="submit" class="btn btn-primary">Upload File</button>
                        </div>
                    </form>
                </div>
                <div class="col-md-6">

                    <form action="{{ route('upload.folder') }}" method="post" enctype="multipart/form-data">

                        @csrf
                        <div class="form-group">
                            <label>Upload Image's Folder</label>
                            <input type="file" name="folder[]" id="folder" multiple directory="" webkitdirectory=""
                                moxdirectory="" class="form-control @error('folder') is-invalid @enderror">
                            @error('folder')
                                <div class="alert alert-danger mt-1" role="alert"><span>{{ $message }}</span>
                                </div>
                            @enderror
                        </div>
                        <div class="form-group mt-4 ml-5">
                            <button type="submit" class="btn btn-primary">Upload Folder</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- Dispaly all products from DB -->
    <div class="card">
        <div class="card-header d-lg-flex justify-content-lg-between">
            <div class="row input-daterange">
                <div class="col-md-2 my-1">
                    <input type="text" name="from_date" id="from_date" class="form-control from-date"
                        placeholder="From Date" readonly />
                </div>
                <div class="col-md-2 my-1">
                    <input type="text" name="to_date" id="to_date" class="form-control to-date" placeholder="To Date"
                        readonly />
                </div>
                <div class="col-md-2 my-1">
                    <select id="type" name="type" class="form-control">
                        <option value="">Select Type</option>
                        <option value="1">New</option>
                        <option value="2">Featured</option>
                        <option value="3">On Sale</option>
                        <option value="4">Free Shipping</option>
                        <option value="5">Latest</option>
                        <option value="6">Accessories</option>
                    </select>
                </div>
                <div class="col-md-6 my-1 text-center">
                    <button type="button" title="Filter" name="filter" id="filter" class="btn btn-primary"><i
                            class="fa fa-filter"></i></button>
                    <button type="button" title="Refresh" name="refresh" id="refresh" class="btn btn-info"><i
                            class="fa fa-undo"></i></button>
                    <a href="javascript:void(0);" title="Export Data (XLSX)" class="btn btn-success"
                        onclick="exportData(0)">
                        <i class="fa fa-file-excel"></i>
                    </a>
                    <a href="javascript:void(0);" title="Export Data (CSV)" class="btn btn-warning" onclick="exportData(1)">
                        <i class="fa fa-file"></i>
                    </a>
                    <a href="{{ asset('/frontend/img/sample-products.xlsx') }}" title="Sample File (XLSX)"
                        class="btn btn-secondary">
                        <i class="fa fa-download"></i>
                    </a>
                </div>
            </div>
            <a href="{{ route('products.create') }}" class="btn btn-dark my-1">Add Product</a>
        </div>
        <div class="card-body">
            <table class="table table-dark table-bordered table-responsive" id="products-table">
                <thead>
                    <th>#</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Code</th>
                    <th>Image</th>
                    <th>Price</th>
                    <th>Selling Price</th>
                    <th>Created On</th>
                    <th>Action</th>
                    <th>Sizes</th>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
@endsection
@section('scripts')
    <script>
        function exportData(type) {
            var from_date = $('#from_date').val();
            var to_date = $('#to_date').val();
            var product_type = $('#type').val();
            var search = $('.dataTables_filter input').val();
            window.location.href = "{{ route('product.export') }}?search=" + search + "&from_date=" + from_date +
                "&to_date=" + to_date + "&type=" + type + "&product_type=" + product_type;
        }

        $(document).ready(function() {

            $('.input-daterange').datepicker({
                todayBtn: 'linked',
                format: 'yyyy-mm-dd',
                autoclose: true
            });

            load_data();

            function load_data(from_date = '', to_date = '', product_type = '') {
                $('#products-table').DataTable({
                    autoWidth: false,
                    order: [0, "DESC"],
                    processing: true,
                    serverSide: true,
                    scrollX: true,
                    searchDelay: 2000,
                    paging: true,
                    "bDestroy": true,
                    // ajax: "{{ route('products.index') }}",
                    ajax: {
                        type: "GET",
                        url: '{{ route('products.index') }}',
                        data: {
                            from_date: from_date,
                            to_date: to_date,
                            product_type: product_type
                        }
                    },
                    columns: [{
                            data: 'id',
                            name: 'id',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'name',
                            name: 'name'
                        },
                        {
                            data: 'category_name',
                            name: 'category_name'
                        },
                        {
                            data: 'code',
                            name: 'code'
                        },
                        {
                            data: 'image',
                            name: 'image',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'price',
                            name: 'price',
                            "mRender": function(data, type, row) {
                                return '<i class="fa fa-rupee-sign"></i> ' + data;
                            },
                            width: "10%"
                        },
                        {
                            data: 'selling_price',
                            name: 'selling_price',
                            "mRender": function(data, type, row) {
                                return '<i class="fa fa-rupee-sign"></i> ' + data;
                            },
                            width: "10%"
                        },
                        {
                            data: 'created_at',
                            name: 'created_at',
                            searchable: false,
                            width: "12%"
                        },
                        {
                            data: 'action',
                            name: 'action',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'size_button',
                            name: 'size_button',
                            orderable: false,
                            searchable: false
                        }
                    ]
                });
            }
            $('#filter').click(function() {
                var from_date = $('#from_date').val();
                var to_date = $('#to_date').val();
                var product_type = $('#type').val();
                if (from_date != '' && to_date != '' && product_type != '') {
                    $('#products-table').DataTable().destroy();
                    load_data(from_date, to_date, product_type);
                } else if (from_date != '' && to_date != '') {
                    $('#products-table').DataTable().destroy();
                    load_data(from_date, to_date);
                } else if (product_type != '') {
                    $('#products-table').DataTable().destroy();
                    load_data('', '', product_type);
                } else {
                    alert('Both Date is required');
                }
            });

            $('#refresh').click(function() {
                $('#from_date').val('');
                $('#to_date').val('');
                $('#type').val('');
                $('#products-table').DataTable().destroy();
                load_data();
            });
        });
    </script>
@endsection
