{{-- @extends('layouts.home')

@section('content') --}}
<section id="products" class="min-h-screen py-24 bg-white">
    <div class="container px-4 mx-auto lg:px-8">
        <div class="container mx-auto">
            <h2 class="mb-12 text-4xl font-bold text-center text-green">Our Products</h2>

            <div class="grid grid-cols-2 gap-6 sm:grid-cols-4 lg:grid-cols-5">
                @foreach ($products as $product)
                    <div
                        class="p-4 overflow-hidden transition-transform transform bg-white rounded-lg shadow-lg hover:scale-105">
                        <img src="{{ $product->image_url ?: asset('images/default-image.jpg') }}"
                            alt="{{ $product->name }}" class="object-cover w-full h-32">
                        <h3 class="mt-2 text-sm font-bold text-green">{{ $product->name }}</h3>
                        {{-- <p class="text-gray-600">{!! $product->description !!}</p> --}}
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>
{{-- @endsection --}}
