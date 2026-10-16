@extends('layouts.app')

@section('title', 'Create Cutting Audit')
@section('header', 'Create Cutting Audit')

@section('content')
<form method="POST" action="{{ route('admin.cutting.audits.store') }}">
    @include('admin.cutting.audits._form')
</form>
@endsection
