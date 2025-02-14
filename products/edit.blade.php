@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">Edit Product</div>

                <div class="card-body">
                    <!-- Display any validation errors -->
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('products.update', $product->id) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PATCH')
                        <div class="row justify-content-around">
			@if(isset($product) && $product->photos->isNotEmpty())
				@foreach($product->photos as $image)

					<div class="form-group">

						<img src='{{asset("frontend/img/$image->images")}}' style="width: 200px;">
						<form action="{{ route('destroyImage', $image->id) }}" method="post">
							@csrf
							@method('DELETE')
							<button type="submit" class="btn btn-danger mt-3">Delete Image</button>
						</form>
					</div>
				@endforeach
			@endif
		</div>
                        <!-- Product Name -->
                        <div class="form-group">
                            <label for="name">Product Name</label>
                            <input id="name" type="text" class="form-control" name="name" value="{{ old('name', $product->name) }}" required autofocus>
                        </div>
                        <div class="form-group">
					<label for="code">Product Code</label>
					<input type="text" name="code" id="code" class="form-control @error('code') is-invalid @enderror" value="{{ isset($product) ? $product->code : old('code') }}">

					@error('code')
						<span class="invalid-feedback" role="alert">
							<strong>{{$message}}</strong>
						</span>
					@enderror
				</div>
				<!-- product category -->
				<div class="form-group">
					<label for="category_id">Product Category</label>
					<select name="category_id" id="category_id" class="form-control @error('category') is-invalid @enderror">
						@if(isset($product))
							<option selected value="{{$product->category->id}}">{{ $product->category->name }}</option>
						@endif
						@foreach($categories as $cat)
							<option value="{{ $cat->id }}">{{ $cat->name }}</option>
						@endforeach
					</select>

					@error('category')
						<span class="invalid-feedback" role="alert">
							<strong>{{$message}}</strong>
						</span>
					@enderror
				</div>
				<!-- product sub-category -->
				<div class="form-group">
					<label for="sub_category_id">Sub-Category</label>
					<select name="sub_category_id" id="sub_category_id" class="form-control @error('sub_category_id') is-invalid @enderror">
						@if(isset($product))		

							@if($product->sub_category_id != null)
								<option selected value="{{$product->subCategory->id}}">{{ $product->subCategory->name }}</option>
							@endif
						@endif
						@foreach($subCategories as $cat)
							<option value="{{ $cat->id }}">{{ $cat->name }}</option>
						@endforeach
					</select>

					@error('sub_category_id')
						<span class="invalid-feedback" role="alert">
							<strong>{{$message}}</strong>
						</span>
					@enderror
				</div>
			</div>

			<!-- product child-sub-category -->
		
			<div class="form-group">
					<label for="child_sub_category_id">Child-Sub-Category</label>
					<select name="child_sub_category_id" id="child_sub_category_id" class="form-control @error('child_sub_category_id') is-invalid @enderror">
					<option value="" disabled selected>Select</option>
					
                    Other Product Details
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" class="form-control" name="description" required>{{ old('description', $product->description) }}</textarea>
                        </div>

                        <!-- Add more form inputs for other product details like price, category, etc. -->

                        <!-- Product Image -->
                        <div class="form-group">
                            <label for="image">Product Image</label>
                            <input id="image" type="file" class="form-control" name="image">
                            @if ($product->image)
                                <img src="{{ asset($product->image) }}" alt="Product Image" class="mt-2" style="max-width: 200px;">
                            @endif
                        </div>
						@foreach($childsubCategories as $cat)
							<option selected value="{{ $cat->id }}">{{ $cat->name }}</option>
						@endforeach
					</select>

					@error('child_sub_category_id')
						<span class="invalid-feedback" role="alert">
							<strong>{{$message}}</strong>
						</span>
					@enderror
				</div>
                        Other Product Details
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" class="form-control" name="description" required>{{ old('description', $product->description) }}</textarea>
                        </div>

                        <!-- Add more form inputs for other product details like price, category, etc. -->

                        <!-- Product Image -->
                        <div class="form-group">
                            <label for="image">Product Image</label>
                            <input id="image" type="file" class="form-control" name="image" accept="image/*">
                            @if ($product->image)
                                <img src="{{ asset($product->image) }}" alt="Product Image" class="mt-2" style="max-width: 200px;">
                            @endif
                        </div>
		
			<!-- product description -->
			<div class="form-group">
				<label for="description">Product Description</label>
				<textarea type="text" name="description" id="description" class="form-control @error('description') is-invalid @enderror">{{ isset($product) ? $product->description : old('description') }}</textarea>

				@error('description')
					<span class="invalid-feedback" role="alert">
						<strong>{{$message}}</strong>
					</span>
				@enderror
			</div>
			<div class="row justify-content-between m-auto">
				<!-- product images -->
				<div class="form-group">
					<label for="images">Product Image</label>
					<input type="file" name="images[]" id="images" accept="image/*" class="form-control @error('images.*') is-invalid @enderror" multiple>

					@error('images.*')
						<span class="invalid-feedback" role="alert">
							<strong>{{'Images should be of types: jpeg,png,jpg,gif,svg'}}</strong>
						</span>
					@enderror
				</div>
				<!-- product price -->
				<div class="form-group">
					<label for="price">Product Price</label>
					<input type="decimal" name="price" id="price" class="form-control @error('price') is-invalid @enderror" value="{{ isset($product) ? $product->price : old('price') }}">

					@error('price')
						<span class="invalid-feedback" role="alert">
							<strong>{{$message}}</strong>
						</span>
					@enderror
				</div>
				<!-- product qty -->
				<div class="form-group" hidden>
					<label for="quantity">Product Quantity</label>
					<input type="number" name="quantity" id="quantity" class="form-control @error('quantity') is-invalid @enderror" value="7">

					@error('quantity')
						<span class="invalid-feedback" role="alert">
							<strong>{{$message}}</strong>
						</span>
					@enderror
				</div>
			</div>
			<!-- product status -->
			<div class="row ml-2">
				<!-- product on sale -->
				<div class="form-group">
					<label for="on_sale">On Sale</label>
					<select name="on_sale" id="on_sale" class="form-control @error('on_sale') is-invalid @enderror">
						<option value="" disabled>Select your option</option>	
						<option value="0" {{ $product->on_sale==0?'selected':''}}>NO</option>
						<option value="1" {{ $product->on_sale==1?'selected':''}}>YES</option>
					</select>

					@error('on_sale')
						<span class="invalid-feedback" role="alert">
							<strong>{{$message}}</strong>
						</span>
					@enderror
				</div>
				
				<div class="form-group ml-5">
					<label for="is_new">New Product</label>
					<select name="is_new" id="is_new" class="form-control @error('is_new') is-invalid @enderror">
						<option value="" disabled>Select your option</option>
						<option value="0" {{ $product->is_new==0?'selected':''}}>NO</option>
						<option value="1" {{ $product->is_new==1?'selected':''}}>YES</option>
					</select>

					@error('is_new')
						<span class="invalid-feedback" role="alert">
							<strong>{{$message}}</strong>
						</span>
					@enderror
				</div>

				<div class="form-group ml-5">
					<label for="is_featured">Featured Product</label>
					<select name="is_featured" id="is_featured" class="form-control @error('is_featured') is-invalid @enderror">
					<option value="" disabled>Select your option</option>	
						<option value="0" {{ $product->is_featured==0?'selected':''}}>NO</option>
						<option value="1" {{ $product->is_featured==1?'selected':''}}>YES</option>
					</select>

					@error('is_featured')
						<span class="invalid-feedback" role="alert">
							<strong>{{$message}}</strong>
						</span>
					@enderror
				</div>

				<div class="form-group ml-5">
					<label for="shipping">Free Shipping</label>
					<select name="shipping" id="shipping" class="form-control @error('shipping') is-invalid @enderror">
					<option value="" disabled>Select your option</option>	
						<option value="0" {{ $product->shipping==0?'selected':''}}>NO</option>
						<option value="1" {{ $product->shipping==1?'selected':''}}>YES</option>
					</select>

					@error('shipping')
						<span class="invalid-feedback" role="alert">
							<strong>{{$message}}</strong>
						</span>
					@enderror
				</div>

			</div>
			<!-- product seo start -->
			<div class="form-group">
				<label for="meta_description">Product Meta Description</label>
				<textarea name="meta_description" class="form-control" placeholder="Make your product visible on search engine by describing your product...">{{ isset($product) ?  $product->meta_description : '' }}</textarea>
			</div>
			<div class="form-group">
				<label for="meta_keywords">Product Meta Keywords</label>
				<textarea name="meta_keywords" class="form-control" placeholder="Seperate keywords using comma...">{{ isset($product) ? $product->meta_keywords : '' }}</textarea>
			</div>

                        <!-- Submit Button -->
                        <button type="submit" class="btn btn-primary">Update Product</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
