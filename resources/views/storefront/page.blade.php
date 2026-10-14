@extends('layouts.storefront')

@section('title', $page->meta_title ?: $page->title)
@section('meta_description', $page->meta_description)

@section('content')
<div class="container mt-4">
    <h1>{{ $page->title }}</h1>
    <div>{!! $page->content !!}</div>
</div>
@endsection