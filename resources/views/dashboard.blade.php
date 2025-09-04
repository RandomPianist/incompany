@extends('layouts.app')

@section('content')
    <div class = "container-fluid h-100 px-3">
        <div class="d-flex align-items-center">
            <select id="dashboard-select" 
                    name="dashboard-select" 
                    class = "mb-2 ml-1 select-dashboard shadow-sm rounded"
                    onchange = "getDadosCards()">
            </select>
        </div>
        
        <div class = "d-flex justify-content-around" style = "overflow-x:auto;padding-bottom:15px">
            <div class = "card-dashboard mx-1 bg-white rounded-lg shadow-sm custom-scrollbar" id = "card-ult-retirada">
                <div class = "header-card-dashboard d-flex justify-content-between border-bottom">
                    <div class = "d-flex flex-column justify-content-center align-items-start ml-3 mr-3">
                        <span class = "titulo-card-dashboard">Últimas retiradas</span>
                    </div>
                </div>
                <div id="card-ult-retirada-dados"></div>
            </div>          

            <div class = "card-dashboard mx-1 bg-white rounded-lg shadow-sm custom-scrollbar" id = "card-retirada-atraso">
                <div class = "header-card-dashboard d-flex justify-content-between border-bottom">
                    <div class = "d-flex flex-column justify-content-center align-items-start ml-3 mr-3">
                        <span class = "titulo-card-dashboard">Qtd. de retiradas em atraso</span>
                    </div>
                </div>
                <div id="card-retirada-atraso-dados"></div>
            </div>

            <div class = "card-dashboard mx-1 bg-white rounded-lg shadow-sm custom-scrollbar" id = "card-retiradas-colab">
                <div class = "header-card-dashboard d-flex justify-content-between border-bottom">
                    <div class = "d-flex flex-column justify-content-center align-items-start ml-3 mr-3">
                        <span class = "titulo-card-dashboard">Retiradas por colaborador</span>
                    </div>
                </div>
                <div id="card-retiradas-colab-dados"></div>
            </div>           

            <div class = "card-dashboard mx-1 bg-white rounded-lg shadow-sm" id = "card-retiradas-centro" style = "min-width:405px;overflow:hidden">
                <div class = "header-card-dashboard d-flex justify-content-between border-bottom">
                    <div class = "d-flex flex-column justify-content-center align-items-start ml-3 mr-3">
                        <span class = "titulo-card-dashboard">Qtd. de retiradas por centro de custo</span>
                    </div>
                </div>
                <div id="card-retiradas-centro-dados"></div>
            </div>

            <div class = "card-dashboard mx-1 bg-white rounded-lg shadow-sm custom-scrollbar" id = "card-cons-centro">
                <div class = "header-card-dashboard d-flex justify-content-between border-bottom">
                    <div class = "d-flex flex-column justify-content-center align-items-start ml-3 mr-3">
                        <span class = "titulo-card-dashboard">Consumo por centro de custo</span>
                    </div>
                </div>
                <div id="card-cons-centro-dados"></div>
            </div>            

            <div class = "card-dashboard mx-1 bg-white rounded-lg shadow-sm custom-scrollbar" id = "card-minhas-maquinas">
                <div class = "header-card-dashboard d-flex justify-content-between border-bottom">
                    <div class = "d-flex flex-column justify-content-center align-items-start ml-3 mr-3">
                        <span class = "titulo-card-dashboard">Minhas máquinas</span>
                    </div>
                </div>
                <div id="card-minhas-maquinas-dados"></div>
            </div>
        </div>
    </div>
    
    @include("components.loader")

    <link rel = "stylesheet" href = "{{ asset('css/especifico/dashboard.css') }}" />
    <script type = "text/javascript" language = "JavaScript" src = "{{ asset('js/lib/highcharts.js') }}"></script>
    <script type = "text/javascript" language = "JavaScript" src = "{{ asset('js/especifico/dashboard.js') }}"></script>
    
    @include('modals.dashboard.itens_em_atraso_modal')
    @include('modals.dashboard.ultimas_retiradas_modal')
    @include('modals.dashboard.retiradas_lista_modal')
    @include('modals.dashboard.retiradas_centro_modal')
@endsection
