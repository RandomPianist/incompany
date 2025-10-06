@extends("layouts.app")

@section("content")
    <div class = "container-fluid h-100 px-3">
        <div class = "row">
            <table class = "w-100">
                <tr>
                    <td class = "w-100">
                        <h3 class = "col header-color mb-3" id = "titulo-tela">{{ $titulo }}</h3>
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
                            <th width = "5%" class = 'nao-ordena'>&nbsp;</th>
                            <th width = "10%" class = "text-right">
                                <span>Código</span>
                            </th>
                            <th width = "25%">
                                <span>Nome</span>
                            </th>
                            <th width = "20%">
                                <span>Empresa</span>
                            </th>
                            <th width = "20%">
                                <span>Centro de custo</span>
                            </th>
                            <th width = "20%" class = "text-center nao-ordena">
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
    
    @if (($titulo == "Usuários" && intval(App\Models\Pessoas::find(Auth::user()->id_pessoa)->supervisor)) || $titulo != "Usuários")
        @include("components.add")
    @endif

    @include("components.loader")

    <link rel = "stylesheet" href = "{{ asset('css/especifico/pessoas.css') }}" />
    
    <script type = "text/javascript" language = "JavaScript">
        const ID = "{{ request('id') ?? '' }}";
        const FILTRO = "{{ request('filtro') ?? '' }}";
        const TIPO = "{{ $titulo }}".charAt(0);
        const IMG_BIOMETRIA = '{{ asset("img/biometria-sim.png") }}';
    </script>

    <script type = "text/javascript" language = "JavaScript" src = "{{ asset('js/especifico/pessoas.js') }}"></script>

    @include("modals.atribuicoes_modal")
    @include("modals.retiradas_modal")
    @include("modals.proximas_retiradas_modal")
@endsection