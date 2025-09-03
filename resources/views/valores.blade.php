@extends("layouts.app")

@section("content")
    <div class = "container-fluid h-100 px-3">
        <div class = "row">
            <table class = "w-100">
                <tr>
                    <td class = "w-100">
                        <h3 class = "col header-color mb-3">{{ $titulo }}</h3>
                    </td>
                    <td class = "ultima-atualizacao">
                        <span class = "custom-label-form">{{ $ultima_atualizacao }}</span>
                    </td>
                </tr>
            </table>
            @include("components.busca")
        </div>
        <div class = "custom-table card">
            <div class = "table-header-scroll">
                <table>
                    <thead>
                        <tr class = "sortable-columns" for = "#table-dados">
                            <th width = "10%" class = "text-right">
                                <span>Código</span>
                            </th>
                            <th width = "@if ($comodato) 30% @else 75% @endif">
                                <span>Descrição</span>
                            </th>
                            @if ($comodato)
                                <th width = "45%">
                                    <span>Locação</span>
                                </th>
                            @endif
                            <th width = "15%" class = "text-center nao-ordena">
                                <span>Ações</span>
                            </th>
                        </tr>
                    </thead>
                </table>
            </div>
            @include("components.table_dados")
        </div>
    </div>

    @include("components.naoencontrado")
    
    @if ($admin)
        @include("components.add")
    @endif
    
    <script type = "text/javascript" language = "JavaScript">
        const ALIAS = "{{ $alias }}";
        const TITULO = "{{ $titulo }}";
        const COMODATO = {{ $comodato ? "true" : "false"}};
    </script>

    <script type = "text/javascript" language = "JavaScript" src = "{{ asset('js/especifico/valores.js') }}"></script>

    @if ($alias == "maquinas")
        @include("modals.estoque_modal")
        @include("modals.comodatos_modal")
    @endif
    
    @include("modals.valores_modal")
@endsection