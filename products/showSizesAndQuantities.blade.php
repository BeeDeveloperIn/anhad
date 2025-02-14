@extends('layouts.app')
@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between">{{ $product->name }} - Manage Sizes and Quantities 
                        <a href="{{route('products.index')}}"> 
                            <button class="btn btn-primary">Back</button>
                        </a>
                    </div>

                    <div class="card-body">
                        <form action="{{ route('products.updateSizesAndQuantities', $product->id) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Size</th>
                                        <th>Quantity</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($sizes as $size)
                                        <tr>
                                            <td>{{ $size->name }}</td>
                                            <td>
                                                <input type="number" min="0" max="100000" name="sizes[{{ $size->id }}]" value="{{ $product->sizes->find($size->id)->pivot->quantity ?? 0 }}" class="form-control">
                                            </td>
                                            <td>
                                                <input id="is_active{{$size->id}}" name="is_active[{{ $size->id }}]" type="hidden" value="{{ isset($product->sizes->find($size->id)->pivot) && $product->sizes->find($size->id)->pivot->is_active?1:0}}">
                                               
                                                <input data-id="{{ $size->id }}" class="toggle-class" name="active[{{ $size->id }}]" type="checkbox" data-onstyle="success" data-offstyle="danger" data-toggle="toggle" data-on="Active" data-off="InActive" {{ isset($product->sizes->find($size->id)->pivot) && $product->sizes->find($size->id)->pivot->is_active ? 'checked' : '' }}>
                                             </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            <button type="submit" class="btn btn-primary">Update Quantities</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('scripts')
<script>
    $(function() {
        $('.toggle-class').change(function() {
            var status = $(this).prop('checked') == true ? 1 : 0; 
            var size_id = $(this).data('id'); 
            $("#is_active"+size_id).val(status);
        })
    });
</script>
@endsection