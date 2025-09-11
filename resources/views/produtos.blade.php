@extends("layouts.app")

@section("content")
    <div class = "container-fluid h-100 px-3">
        <div class = "row">
            <table class = "w-100">
                <tr>
                    <td class = "w-100">
                        <h3 class = "col header-color mb-3">Produtos</h3>
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
                            <th width = "5%" class = "nao-ordena">&nbsp;</span>
                            <th width = "20%" class = "text-center">
                                <span>Código Kx-safe</span>
                            </th>
                            <th width = "27.5%">
                                <span>Descrição</span>
                            </th>
                            <th width = "27.5%">
                                <span>Categoria</span>
                            </th>
                            <th width = "10%" class = "text-right">
                                <span>Preço</span>
                            </th>
                            <th width = "10%" class = "text-center nao-ordena">
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

    <script type = "text/javascript" language = "JavaScript">
        const ID = "{{ request('id') ?? '' }}";
        const FILTRO = "{{ request('filtro') ?? '' }}";
    </script>
    
    <script type = "text/javascript" language = "JavaScript" src = "{{ asset('js/especifico/produtos.js') }}"></script>

    @include("modals.produtos_modal")
@endsection