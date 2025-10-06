@extends('layouts.auth')
@section('content')
    <div class = "container-login d-flex justify-center align-items-center">
        <form method = "POST" action = "{{ route('login') }}" class="d-flex flex-column justify-center align-center">
            <img src = "{{ asset('img/logo.png') }}" alt = "Logo" class = "mb-4 w-75 mx-auto">
            @csrf
            <div class="mx-2 mt-5 mb-2">
                <input class = "form-control" id = "email" type = "email" name = "email" required = "required" autofocus = "autofocus" 
                placeholder = "Email"/>
            </div>
            <div class="mx-2 my-2">
                <input class = "form-control" id = "password" type = "password" 
                name = "password" required = "required" autocomplete = "current-password" placeholder = "Senha" />
            </div>
            <div class="mx-2 mt-5">
                <button type = "submit" class = "btn btn-primary w-100">Continuar</button>
            </div>
        </form>
    </div>
    @if ($errors->any())
        <script type = "text/javascript" language = "JavaScript" src = "{{ asset('js/lib/sweetalert2.js') }}"></script>
        <script type = "text/javascript" language = "JavaScript" src = "{{ asset('js/geral/alerta.js')    }}"></script>
        <script type = "text/javascript" language = "JavaScript">
            window.onload = function() {
                s_alert({
                    icon : "error",
                    html : "{{ $errors->first() }}"
                });
            }
        </script>
    @endif
@endsection