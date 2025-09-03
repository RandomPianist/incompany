@extends("layouts.app")

@section("content")
    <div class = "container-fluid h-100 px-3">
        <div class = "row">
            <table class = "w-100">
                <tr>
                    <td class = "w-100">
                        <h3 class = "col header-color mb-3">Centro de custos</h3>
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
                            <th width = "35%">
                                <span>Descrição</span>
                            </th>
                            <th width = "40%">
                                <span>Empresa</span>
                            </th>
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
    @include("components.add")
    
    <script type = "text/javascript" language = "JavaScript" src = "{{ asset('js/especifico/setores.js') }}"></script>

    @include("modals.setores_modal")
    @include("modals.atribuicoes_modal")
    @include("modals.detalhar_atb_modal")
@endsection