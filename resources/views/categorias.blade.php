@extends("layouts.app")

@section("content")
    <div class = "container-fluid h-100 px-3">
        <div class = "row">
            <table class = "w-100">
                <tr>
                    <td class = "w-100">
                        <h3 class = "col header-color mb-3">Categorias</h3>
                    </td>
                    <td class = "ultima-atualizacao">
                        <span class = "custom-label-form">{{ $ultima_atualizacao }}</span>
                    </td>
                </tr>
            </table>
            <x-busca />
        </div>
        <div class = "custom-table card">
            <div class = "table-header-scroll">
                <table>
                    <thead>
                        <tr class = "sortable-columns" for = "#table-dados">
                            <th width = "10%" class = "text-right">
                                <span>Código</span>
                            </th>
                            <th width = "75%">
                                <span>Descrição</span>
                            </th>
                            <th width = "15%" class = "text-center nao-ordena">
                                <span>Ações</span>
                            </th>
                        </tr>
                    </thead>
                </table>
            </div>
            <x-table_dados />
        </div>
    </div>

    <x-naoencontrado />
    
    @if ($admin)
        <x-add />
    @endif

    <script type = "text/javascript" language = "JavaScript">
        const ID = "{{ request('id') ?? '' }}";
        const FILTRO = "{{ request('filtro') ?? '' }}";
    </script>

    <script type = "text/javascript" language = "JavaScript" src = "{{ asset('js/especifico/categorias.js') }}"></script>

    @include("modals.categorias_modal")
@endsection