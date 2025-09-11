@extends("layouts.app")

@section("content")
    <div class = "container-fluid h-100 px-3">
        <div class = "row">
            <table class = "w-100">
                <tr>
                    <td class = "w-100">
                        <h3 class = "col header-color mb-3">Empresas</h3>
                    </td>
                    <td class = "ultima-atualizacao">
                        <span class = "custom-label-form">{{ $ultima_atualizacao }}</span>
                    </td>
                </tr>
            </table>
        </div>
        <div id = "principal" role = "main" class = "main"></div>
    </div>

    @if ($admin)
        @include("components.add")
    @endif

    <link rel = "stylesheet" href = "{{ asset('css/especifico/empresas.css') }}" />

    <script type = "text/javascript" language = "JavaScript">
        const ID = "{{ request('id') ?? '' }}";
        const GRUPO = "{{ request('grupo') ?? '' }}";
    </script>
    
    <script type = "text/javascript" language = "JavaScript" src = "{{ asset('js/especifico/empresas.js') }}"></script>

    @include("modals.empresas_modal")
@endsection
