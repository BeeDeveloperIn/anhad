@extends('layouts.app')

@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <h3>{{ isset($product) ? 'Update Product' : 'Add Product' }}</h3>
            <a href="{{ route('products.index') }}">
                <button class="btn btn-primary">Back</button>
            </a>
        </div>
        <div class="card-body">
            <!-- product images -->
            <div class="row justify-content-around">
                @if (isset($product) && $product->photos->isNotEmpty())
                    @foreach ($product->photos as $image)
                        <div class="form-group col-lg-2 col-md-4 col-sm-6">
                            <a data-fancybox="gallery{{ $product->id }}"
                                href="{{ asset("{$product->getImage($image->images, false)}") }}">
                                <img class="admin-product-img" src='{{ asset("{$product->getImage($image->images)}") }}'>
                            </a>
                            <form action="{{ route('destroyImage', $image->id) }}" method="post">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger mt-3 btn-sm">Delete Image</button>
                            </form>
                        </div>
                    @endforeach
                @endif
            </div>
            <!-- product attributes start-->
            @if (isset($attributes))
                <table class="table table-dark table-bordered table-hover">
                    <thead>
                        <th>Attribute Name</th>
                        <th>Attribute Value</th>
                        <th>Delete</th>
                    </thead>
                    <tbody>
                        @foreach ($attributes as $at)
                            <tr>
                                <td>{{ $at->attribute_name }}</td>
                                <td>{{ $at->attribute_value }}</td>
                                <td>
                                    <form action="{{ route('destroyAttribute', $at->slug) }}" method="post">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
            <!-- product attributes end-->
            <form action="{{ isset($product) ? route('products.update', $product->id) : route('products.store') }}"
                method="post" enctype="multipart/form-data">
                @csrf
                @if (isset($product))
                    @method('PATCH')
                @endif
                <div class="row justify-content-between m-auto">
                    <!-- product name -->
                    <div class="col-md-4 form-group">
                        <label for="name">Product Name</label>
                        <input type="text" name="name" id="name"
                            class="form-control @error('name') is-invalid @enderror"
                            value="{{ isset($product) ? $product->name : old('name') }}">

                        @error('name')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                    <!-- product code -->
                    <div class="col-md-4 form-group">
                        <label for="code">Product Code</label>
                        <input type="text" name="code" id="code"
                            class="form-control @error('code') is-invalid @enderror"
                            value="{{ isset($product) ? $product->code : old('code') }}">

                        @error('code')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                    <!-- product category -->
                    <div class="col-md-4 form-group">
                        <label for="category_id">Product Category</label>
                        <select name="category_id" id="category_id"
                            class="form-control @error('category_id') is-invalid @enderror">
                            <option value="">--Select--</option>

                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}"
                                    {{ isset($product) && $product->category_id == $cat->id ? 'selected' : '' }}>
                                    {{ $cat->name }}</option>
                            @endforeach
                        </select>

                        @error('category_id')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                    <!-- product sub-category -->
                    <div class="col-md-6 form-group">
                        <label for="sub_category_id">Sub-Category</label>
                        <select name="sub_category_id" id="sub_category_id"
                            class="form-control @error('sub_category_id') is-invalid @enderror">
                            @foreach ($subCategories as $cat)
                                <option value="{{ $cat->id }}"
                                    {{ isset($product) && $product->sub_category_id == $cat->id ? 'selected' : '' }}>
                                    {{ $cat->name }}</option>
                            @endforeach
                        </select>

                        @error('sub_category_id')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                    <div class="col-md-6 form-group">
                        <label for="child_sub_category_id">Child-Sub-Category</label>
                        <select name="child_sub_category_id" id="child_sub_category_id"
                            class="form-control @error('child_sub_category_id') is-invalid @enderror">
                            <option value="">Select</option>

                            @foreach ($childsubCategories as $cat)
                                <option value="{{ $cat->id }}"
                                    {{ isset($product) && $product->child_sub_category_id === $cat->id ? 'selected' : '' }}>
                                    {{ $cat->name }}</option>
                            @endforeach
                        </select>

                        @error('child_sub_category_id')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                    <!-- product description -->
                    <div class="col-12 form-group">
                        <label for="description">Product Description</label>
                        <textarea type="text" name="description" id="description"
                            class="form-control @error('description') is-invalid @enderror">{{ isset($product) ? $product->description : old('description') }}</textarea>

                        @error('description')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                    <!-- product images -->
                    <div class="col-4 form-group">
                        <label for="images">Product Image</label>
                        <input type="file" name="images[]" id="images" accept="image/*"
                            class="form-control @error('images') is-invalid @enderror @error('images.*') is-invalid @enderror"
                            multiple>
                        @error('images')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                        @error('images.*')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ 'Images should be of types: jpeg,png,jpg,gif,svg' }}</strong>
                            </span>
                        @enderror
                    </div>
                    <!-- product price -->
                    <div class="col-4 form-group">
                        <label for="price">Product Price</label>
                        <input type="decimal" name="price" id="price"
                            class="form-control @error('price') is-invalid @enderror"
                            value="{{ isset($product) ? $product->price : old('price') }}">

                        @error('price')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <!-- selling price -->
                    <div class="col-4 form-group">
                        <label for="selling_price">Selling Price</label>
                        <input type="decimal" name="selling_price" id="selling_price"
                            class="form-control @error('selling_price') is-invalid @enderror"
                            value="{{ isset($product) ? $product->selling_price : old('selling_price') }}">

                        @error('selling_price')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                    <!-- product status -->
                    <!-- product on sale -->
                    <div class="col-md-4 form-group">
                        <label for="on_sale">On Sale</label>
                        <select name="on_sale" id="on_sale"
                            class="form-control @error('on_sale') is-invalid @enderror">
                            <option value="0" {{ isset($product) && $product->on_sale == 0 ? 'selected' : '' }}>
                                NO
                            </option>
                            <option value="1" {{ isset($product) && $product->on_sale == 1 ? 'selected' : '' }}>
                                YES
                            </option>
                        </select>

                        @error('on_sale')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <div class="col-md-4 form-group">
                        <label for="is_new">New Product</label>
                        <select name="is_new" id="is_new"
                            class="form-control @error('is_new') is-invalid @enderror">
                            <option value="0" {{ isset($product) && $product->is_new == 0 ? 'selected' : '' }}>NO
                            </option>
                            <option value="1" {{ isset($product) && $product->is_new == 1 ? 'selected' : '' }}>
                                YES
                            </option>
                        </select>

                        @error('is_new')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <div class="col-md-4 form-group">
                        <label for="is_featured">Featured Product</label>
                        <select name="is_featured" id="is_featured"
                            class="form-control @error('is_featured') is-invalid @enderror">
                            <option value="0" {{ isset($product) && $product->is_featured == 0 ? 'selected' : '' }}>
                                NO
                            </option>
                            <option value="1" {{ isset($product) && $product->is_featured == 1 ? 'selected' : '' }}>
                                YES
                            </option>
                        </select>

                        @error('is_featured')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="shipping">Free Shipping</label>
                        <select name="shipping" id="shipping"
                            class="form-control @error('shipping') is-invalid @enderror">
                            <option value="0" {{ isset($product) && $product->shipping == 0 ? 'selected' : '' }}>
                                NO
                            </option>
                            <option value="1" {{ isset($product) && $product->shipping == 1 ? 'selected' : '' }}>
                                YES
                            </option>
                        </select>

                        @error('shipping')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <div class="col-md-4 form-group">
                        <label for="is_latest">Latest Product</label>
                        <select name="is_latest" id="is_latest"
                            class="form-control @error('is_latest') is-invalid @enderror">
                            <option value="0" {{ isset($product) && $product->is_latest == 0 ? 'selected' : '' }}>
                                NO
                            </option>
                            <option value="1" {{ isset($product) && $product->is_latest == 1 ? 'selected' : '' }}>
                                YES
                            </option>
                        </select>

                        @error('is_latest')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="is_accessories">Is Related to Accessories</label>
                        <select name="is_accessories" id="is_accessories"
                            class="form-control @error('is_accessories') is-invalid @enderror">
                            <option value="0"
                                {{ isset($product) && $product->is_accessories == 0 ? 'selected' : '' }}>
                                NO
                            </option>
                            <option value="1"
                                {{ isset($product) && $product->is_accessories == 1 ? 'selected' : '' }}>
                                YES
                            </option>
                        </select>

                        @error('is_accessories')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                    <!-- product quantity -->
                    <div
                        class="col-12 form-group {{ isset($product) && $product->is_accessories == 1 ? '' : 'd-none' }} quantity-div">
                        <label for="quantity">Product Quantity</label>
                        <input type="number" min="0" name="quantity" id="quantity"
                            class="form-control @error('quantity') is-invalid @enderror"
                            value="{{ isset($product) ? $product->quantity : old('quantity') }}">

                        @error('quantity')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                    @if (isset($product))
                        <div class="col-12 form-group">
                            <label for="created_at">Created At</label>
                            <input type="datetime-local" name="created_at"
                                class="form-control @error('created_at')
is-invalid
@enderror"
                                value="{{ isset($product) ? $product->created_at : old('created_at') }}">
                            @error('created_at')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    @endif
                    <div class="col-12 form-group">
                        <label for="meta_description">Product Meta Description</label>
                        <textarea name="meta_description" class="form-control"
                            placeholder="Make your product visible on search engine by describing your product...">{{ isset($product) ? $product->meta_description : '' }}</textarea>
                    </div>
                    <div class="col-12 form-group">
                        <label for="meta_keywords">Product Meta Keywords</label>
                        <textarea name="meta_keywords" class="form-control" placeholder="Seperate keywords using comma...">{{ isset($product) ? $product->meta_keywords : '' }}</textarea>
                    </div>
                    <!-- product notes -->
                    <div class="col-12 form-group">
                        <label for="notes">Product Notes</label>
                        <textarea type="text" name="notes" id="notes" class="form-control @error('notes') is-invalid @enderror">{{ isset($product) ? $product->notes : old('notes') }}</textarea>
                        @error('notes')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                    <!-- product seo start -->
                    <div class="col-12 form-group">
                        <button
                            class="btn btn-primary">{{ isset($product) ? 'Update Product Details' : 'Add Product' }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <!-- Add this script at the bottom of your Blade template -->


@endsection

@section('script')
    <script>
        // Update select elements on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Call the updateSelect function for each select element
            updateSelect('on_sale', '{{ isset($product) ? $product->on_sale : old('on_sale') }}');
            updateSelect('is_new', '{{ isset($product) ? $product->is_new : old('is_new') }}');
            updateSelect('is_featured', '{{ isset($product) ? $product->is_featured : old('is_featured') }}');
            updateSelect('shipping', '{{ isset($product) ? $product->shipping : old('shipping') }}');
        });

        // Function to update select element
        function updateSelect(id, value) {
            document.getElementById(id).value = value;
        }
    </script>
@endsection
@section('scripts')
    <script>
        $(document).ready(function() {
            let is_accessories = '{{ isset($product) && $product->is_accessories == 1 ? 1 : 0 }}';
            var idCategory = $("#category_id").val();
            var selectedSubCategory = '{{ isset($product) ? $product->sub_category_id : '' }}';
            var selectedChildSubCategory = '{{ isset($product) ? $product->child_sub_category_id : '' }}';

            if (is_accessories == 1) {
                $("#quantity").prop('min', 0);
            } else {
                $("#quantity").prop('min', 0);
            }
            /*------------------------------------------
            --------------------------------------------
            Category Dropdown Change Event
            --------------------------------------------
            --------------------------------------------*/
            $('#category_id').on('change', function() {
                var idCategory = this.value;
                getSubCategories(idCategory);
            });

            $('#is_accessories').on('change', function() {
                var is_true = this.value;
                if (is_true == 1) {
                    $("#quantity").prop('min', 1);
                    $(".quantity-div").removeClass("d-none");
                } else {
                    $("#quantity").prop('min', 0);
                    $(".quantity-div").addClass("d-none");
                }
            });

            if (idCategory != undefined || idCategory != '') {
                getSubCategories(idCategory);
            }

            function getSubCategories(idCategory) {
                $("#sub_category_id").html('');
                $.ajax({
                    url: "{{ route('fetch-subcategories') }}",
                    type: "POST",
                    data: {
                        category_id: idCategory,
                        _token: '{{ csrf_token() }}'
                    },
                    dataType: 'json',
                    success: function(result) {
                        $('#sub_category_id').html('<option value="">-- Select --</option>');
                        var option = '';
                        $.each(result.subCategories, function(key, value) {
                            if (selectedSubCategory != '' && selectedSubCategory == value.id) {
                                option = '<option value="' + value
                                    .id + '"  selected>' + value.name + '</option>';
                            } else {
                                option = '<option value="' + value
                                    .id + '">' + value.name + '</option>'
                            }
                            $("#sub_category_id").append(option);
                        });
                        //$('#child_sub_category_id').html('<option value="">-- Select --</option>');
                    }
                });
            }
            /*------------------------------------------
            --------------------------------------------
            Sub Category Dropdown Change Event
            --------------------------------------------
            --------------------------------------------*/

            $(document).on('change', '#sub_category_id', function() {
                var sub_category_id = this.value;
                getChildSubCategories(sub_category_id);
            });

            if (selectedSubCategory != '') {
                getChildSubCategories(selectedSubCategory);
            }

            function getChildSubCategories(sub_category_id) {
                $("#child_sub_category_id").html('');
                $.ajax({
                    url: "{{ route('fetch-child-subcategories') }}",
                    type: "POST",
                    data: {
                        sub_category_id: sub_category_id,
                        _token: '{{ csrf_token() }}'
                    },
                    dataType: 'json',
                    success: function(res) {
                        var option = '';
                        $('#child_sub_category_id').html('<option value="">-- Select --</option>');
                        $.each(res.childSubCategories, function(key, value) {
                            if (selectedChildSubCategory != '' && selectedChildSubCategory ==
                                value.id) {
                                option = '<option value="' + value
                                    .id + '"  selected>' + value.name + '</option>';
                            } else {
                                option = '<option value="' + value
                                    .id + '">' + value.name + '</option>'
                            }
                            $("#child_sub_category_id").append(option);
                        });
                    }
                });
            }
        });
    </script>
@endsection
