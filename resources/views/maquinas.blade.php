@extends("layouts.app")

@section("content")
    <div class = "container-fluid h-100 px-3">
        <div class = "row">
            <table class = "w-100">
                <tr>
                    <td class = "w-100">
                        <h3 class = "col header-color mb-3">Máquinas</h3>
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
                            <th width = "12%" class = "text-right">
                                <span>Código</span>
                            </th>
                            <th width = "@if ($comodato) 28% @else 69% @endif">
                                <span>Descrição</span>
                            </th>
                            @if ($comodato)
                                <th width = "35%">
                                    <span>Contrato</span>
                                </th>
                            @endif
                            <th width = "25%" class = "text-center nao-ordena">
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

    <link rel = "stylesheet" href = "{{ asset('css/especifico/maquinas.css') }}" />
    
    <script type = "text/javascript" language = "JavaScript">
        const ID = "{{ request('id') ?? '' }}";
        const FILTRO = "{{ request('filtro') ?? '' }}";
        const COMODATO = {{ $comodato ? "true" : "false"}};
    </script>

    <script type = "text/javascript" language = "JavaScript" src = "{{ asset('js/especifico/maquinas.js')  }}"></script>
    <script type = "text/javascript" language = "JavaScript" src = "{{ asset('js/especifico/estoque.js')   }}"></script>
    <script type = "text/javascript" language = "JavaScript" src = "{{ asset('js/especifico/comodatos.js') }}"></script>

    @include("modals.contrato_modal")
    @include("modals.atribuicoes_modal")
    @include("modals.estoque_modal")
    @include("modals.cp_modal")
    @include("modals.comodatos_modal")    
    @include("modals.maquinas_modal")
@endsection