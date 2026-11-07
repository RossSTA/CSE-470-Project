@extends('layouts.app')
@section('content')<h1>Edit portfolio project</h1><form class="card" method="post" action="{{ route('projects.update',$portfolioItem) }}">@csrf @method('PUT')@include('profile.project-fields')<div class="actions"><button>Save project</button><a href="{{ route('profile.edit',$portfolioItem->profile) }}">Cancel</a></div></form>@endsection
