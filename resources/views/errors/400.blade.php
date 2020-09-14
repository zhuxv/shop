@extends('errors::minimal')

@section('title', __('Request Params Error'))
@section('code', '400')
@section('message', __($exception->getMessage() ?: 'Request Params Error'))
