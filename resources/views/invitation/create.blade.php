@extends('layouts.dashboard')

@section('content')

    @include("errors.list")

    <div class="container-fluid">

            {!! Form::open(['url'=>"tournaments", 'class'=>'stepy-validation']) !!}

            @include("tournaments.form", ["submitButton" => trans('crud.addModel',['currentModelName' => $currentModelName]) ])


            {!! Form::close()!!}

    </div>
@stop