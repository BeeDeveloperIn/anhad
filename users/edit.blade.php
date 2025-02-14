@extends('layouts.app')
@section('css')
    <style>
        .invalid-feedback {
            display: block !important;
            color: #c53125;
        }
    </style>
@endsection
@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between">Update User

            <a href="{{ url()->previous() }}">
                <button class="btn btn-primary">Back</button>
            </a>
        </div>
        <div class="card-body">
            <form action="{{ route('users.update', $user->id) }}" method="post" enctype="multipart/form-data"
                id="user-admin-form">
                @csrf
                @method('PATCH')
                <!-- Company name -->
                <div class="row">
                    <div class="form-group col-12">
                        <label for="name">Name</label>
                        <input type="text" name="name" id="name"
                            class="form-control @error('name') is-invalid @enderror" value="{{ $user->name }}"
                            placeholder="Enter Name">
                        @error('name')
                            <span class="invalid-feedback" role="alert">
                                <strong> {{ $message }} </strong>
                            </span>
                        @enderror
                    </div>
                    <div class="form-group col-6">
                        <div class="phone-input-w-100">
                            <label for="contact_number" style="display: block">Contact Number</label>
                            <input type="tel" name="contact_number" id="contact_number"
                                class="form-control @error('contact_number') is-invalid @enderror"
                                value="{{ $user->contact_number ?? old('contact_number') }}"
                                placeholder="Enter Contact Number">
                        </div>
                        @error('contact_number')
                            <span class="invalid-feedback mt-3" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                        @if (!$errors->has('contact_number'))
                            <span class="invalid-feedback d-none" id="no-error" role="alert">
                            </span>
                        @endif
                        <input type="hidden" id="country_code" name="country_code"
                            value="{{ $user->country_code ?? old('country_code') }}">
                        <input type="hidden" id="iso_code" name="iso_code"
                            value="{{ $user->iso_code ?? old('iso_code') }}">
                    </div>
                    <!-- User Email address -->
                    <div class="form-group col-6">
                        <label for="address">Email (optional)</label>
                        <input type="email" name="email" id="email" placeholder="Enter Email Address"
                            class="form-control @error('email') is-invalid @enderror" value="{{ $user->email }}">
                        @error('email')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <div class="form-group col-12">
                        <label for="contact_address">Contact Address</label>
                        <input type="text" name="contact_address"
                            class="form-control @error('contact_address') is-invalid @enderror"
                            placeholder="Enter Contact Address"
                            value="{{ $user->contact_address ?? old('contact_address') }}">
                        @error('contact_address')
                            <span class="invalid-feedback mt-3" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
@endsection
@section('scripts')
    <script>
        (function() {
            var phone_number = window.intlTelInput(document.querySelector("#contact_number"), {
                separateDialCode: true,
                preferredCountries: ["in"],
                initialCountry: "{{ $user->iso_code ?? 'in' }}",
                hiddenInput: "full_number",
                formatAsYouType: false,
                formatOnDisplay: false,
                utilsScript: "//cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.3/js/utils.js"
            });

            // Function to get the selected country code
            function getCountryCode() {
                var countryData = phone_number.getSelectedCountryData(); // Get selected country data
                return countryData; // Return country dial code
            }

            // Display selected country code
            function updateCountryCode() {
                $("#no-error").html("");
                $("#country_code").val(getCountryCode().dialCode);
                $("#iso_code").val(getCountryCode().iso2);
            }

            // Update country code when the input changes
            $("#contact_number").on('countrychange', function() {
                $("#no-error").html("");
                updateCountryCode();
            });

            // Initial update
            updateCountryCode();

            document.getElementById('user-admin-form').onsubmit = function() {
                if (phone_number) {
                    if (phone_number.isValidNumber()) {
                        $("#no-error").addClass("d-none");
                        return true;
                    } else {
                        $("#no-error").removeClass("d-none");
                        $("#no-error").html("<strong>Invalid Contact Number</strong>");
                    }
                    return false;
                }
                return true;
            };
        })();
    </script>
@endsection
