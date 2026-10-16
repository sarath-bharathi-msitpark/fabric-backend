@extends('layouts.app')

@section('title', 'Edit ' . $audit->audit_no)
@section('header', 'Edit Audit ' . $audit->audit_no)

@section('content')
<form method="POST" action="{{ route('admin.cutting.audits.update', $audit) }}">
    @include('admin.cutting.audits._form', ['audit' => $audit])
</form>
@endsection
