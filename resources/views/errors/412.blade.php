@extends('errors::minimal')

@section('title', __('Token Failure'))
@section('code', '412')
@section('message', __($exception->getMessage() ?: 'Token Failure'))
