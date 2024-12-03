@extends("layouts.app")

@section("content")
    <div class = "container-fluid h-100 px-3">
    <div class = "d-flex justify-content-around">
        <div class = "card-dashboard mx-1 w-100 bg-white rounded-lg shadow-sm custom-scrollbar">
            <div class = "header-card-dashboard d-flex justify-content-between border-bottom">
                <div class = "d-flex flex-column justify-content-center align-items-start ml-3">
                    <span class = "titulo-card-dashboard">Últimas retiradas</span>
                </div>    
                <div class = "align-self-start m-3">
                    <i class = "fa-solid fa-ellipsis-vertical"></i>
                </div>
            </div>
            @if (sizeof($ultimas_retiradas))
                <div class = "d-flex justify-content-center">
                    <table class = "table tabela-funcionarios-dashboard cProd">
                        @foreach ($ultimas_retiradas as $retirada)
                            <tr onclick = "retiradas({{ $retirada->id }})">
                                <td width = "20%" class = "td-foto text-center">
                                    <img class = 'foto-funcionario-dashboard' src = '{{ $retirada->foto }}' onerror = "this.classList.add('d-none');this.nextElementSibling.classList.remove('d-none')" />
                                    <i class = 'fas fa-user d-none'></i>
                                </td>
                                <td width="80%" class = "td-nome">{{ $retirada->nome }}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            @else
                <div class = "d-flex flex-column align-items-center m-5">
                    <span>Não há nada a mostrar</span>
                </div>
            @endif
        </div>
        <div class = "card-dashboard mx-1 w-100 bg-white rounded-lg shadow-sm custom-scrollbar">
            <div class = "header-card-dashboard d-flex justify-content-between border-bottom">
                <div class = "d-flex flex-column justify-content-center align-items-start ml-3">
                    <span class = "titulo-card-dashboard">Retiradas em atraso</span>
                </div>    
                <div class = "align-self-start m-3">
                    <i class = "fa-solid fa-ellipsis-vertical"></i>
                </div>
            </div>
            @if (sizeof($pessoas))
                <div class = "d-flex justify-content-center">
                    <table class = "table tabela-funcionarios-dashboard cProd">
                        @foreach ($pessoas as $pessoa)
                            <tr onclick = "produtosEmAtraso({{ $pessoa->id }})">
                                <td width = "20%" class = "td-foto text-center">
                                    <img class = 'foto-funcionario-dashboard' src = '{{ $pessoa->foto }}' onerror = "this.classList.add('d-none');this.nextElementSibling.classList.remove('d-none')" />
                                    <i class = 'fas fa-user d-none'></i>
                                </td>
                                <td width="85%" class = "td-nome">{{ $pessoa->nome }}</td>
                                <td class="text-right" width = "5%">
                                    <div class = "numerico">
                                        {{ $pessoa->total }}
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            @else
                <div class = "d-flex flex-column align-items-center m-5">
                    <span>Não há nada a mostrar</span>
                </div>
            @endif
        </div>
        <div class = "card-dashboard mx-1 w-100 bg-white rounded-lg shadow-sm custom-scrollbar">
            <div class = "header-card-dashboard d-flex justify-content-between border-bottom">
                <div class = "d-flex flex-column justify-content-center align-items-start ml-3">
                    <span class = "titulo-card-dashboard">Retiradas por centro de custo</span>
                </div>    
                <div class = "align-self-start m-3">
                    <i class = "fa-solid fa-ellipsis-vertical"></i>
                </div>
            </div>
            <div>
                @if (sizeof($retiradas_por_setor))
                    <figure class="highcharts-figure">
                        <div id="container"></div>
                    </figure>
                @else
                    <div class = "d-flex flex-column align-items-center m-5">
                        <span>Não há nada a mostrar</span>
                    </div>
                @endif
            </div>
        </div>
        <div class = "card-dashboard mx-1 w-100 bg-white rounded-lg shadow-sm custom-scrollbar">
            <div class = "header-card-dashboard d-flex justify-content-between border-bottom">
                <div class = "d-flex flex-column justify-content-center align-items-start ml-3">
                    <span class = "titulo-card-dashboard">Minhas máquinas</span>
                </div>    
                <div class = "align-self-start m-3">
                    <i class = "fa-solid fa-ellipsis-vertical"></i>
                </div>
            </div>
            <div>
                @if (sizeof($minhas_maquinas))
                    <div class = "d-flex justify-content-center">
                        <table class = "table tabela-funcionarios-dashboard cProd">
                            @foreach ($minhas_maquinas as $maquina)
                                <tr onclick = "extrato_maquina({{ $maquina->id }})">
                                    <td width = "20%" class = "td-foto text-center">
                                        <img class = 'foto-funcionario-dashboard' src = '{{ asset("img/maquinas.png") }}' />
                                    </td>
                                    <td width = "80%" class = "td-nome">{{ $maquina->descr }}</td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                @else
                    <div class = "d-flex flex-column align-items-center m-5">
                        <span>Não há nada a mostrar</span>
                    </div>
                @endif
            </div>
        </div>
        <div class = "card-dashboard mx-1 w-100 bg-white rounded-lg shadow-sm custom-scrollbar">
            <div class = "header-card-dashboard d-flex justify-content-between border-bottom">
                <div class = "d-flex flex-column justify-content-center align-items-start ml-3">
                    <span class = "titulo-card-dashboard">Ranking de retiradas</span>
                </div>    
                <div class = "align-self-start m-3">
                    <i class = "fa-solid fa-ellipsis-vertical"></i>
                </div>
            </div>
            @if (sizeof($ranking))
                <div class = "d-flex justify-content-center">
                    <table class = "table tabela-funcionarios-dashboard cProd">
                        @foreach ($ranking as $item)
                            <tr onclick = "retiradas({{ $item->id }})">
                                <td width = "20%" class = "td-foto text-center">
                                    <img class = 'foto-funcionario-dashboard' src = '{{ $item->foto }}' onerror = "this.classList.add('d-none');this.nextElementSibling.classList.remove('d-none')" />
                                    <i class = 'fas fa-user d-none'></i>
                                </td>
                                <td width="85%" class = "td-nome">{{ $item->nome }}</td>
                                <td class="text-right" width = "5%">
                                    <div class = "numerico">
                                        {{ number_format($item->retirados, 0) }}
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            @else
                <div class = "d-flex flex-column align-items-center m-5">
                    <span>Não há nada a mostrar</span>
                </div>
            @endif
        </div>
    </div>

    <script type = "text/javascript" language = "JavaScript">
        function listar() {
            @if(sizeof($retiradas_por_setor))
                Highcharts.chart('container', {
                    chart: {
                        type: 'pie',
                        custom: {},
                        events: {
                            render() {
                                const chart = this,
                                    series = chart.series[0];
                                let customLabel = chart.options.chart.custom.label;

                                if (!customLabel) {
                                    customLabel = chart.options.chart.custom.label =
                                        chart.renderer.label(
                                            'Total<br/>' +
                                            '<strong>{{ $total }}</strong>'
                                        )
                                            .css({
                                                color: '#000',
                                                textAnchor: 'middle'
                                            })
                                            .add();
                                }

                                const x = series.center[0] + chart.plotLeft,
                                    y = series.center[1] + chart.plotTop - (customLabel.attr('height') / 2);

                                customLabel.attr({ x, y });
                                // Set font size based on chart diameter
                                customLabel.css({ fontSize: `${series.center[2] / 12}px` });
                            }
                        }
                    },
                    accessibility: {
                        point: {
                            valueSuffix: '%'
                        }
                    },
                    title: {
                        text: ' '
                    },
                    tooltip: {
                        // Mostrar setor, quantidade absoluta e percentual
                        pointFormat: '<b>{point.y}</b> ({point.percentage:.0f}%)'
                    },
                    legend: {
                        enabled: true,
                        labelFormatter: function() {
                            // Mostrar nome do setor e quantidade absoluta na legenda
                            return this.name + ': ' + this.y;
                        }
                    },
                    plotOptions: {
                        series: {
                            allowPointSelect: true,
                            cursor: 'pointer',
                            borderRadius: 8,
                            dataLabels: [{
                                enabled: true,
                                distance: 20,
                                format: '{point.name}'
                            }, {
                                enabled: true,
                                distance: -15,
                                format: '{point.percentage:.0f}%',
                                style: {
                                    fontSize: '0.9em'
                                }
                            }],
                            showInLegend: true
                        }
                    },
                    series: [{
                        name: 'Retiradas',
                        colorByPoint: true,
                        innerSize: '75%',
                        data: [
                            @for($cont = 0; $cont < sizeof($retiradas_por_setor);  $cont++)
                                {
                                    name: "{{ $retiradas_por_setor[$cont]->descr }}",
                                    y: {{ $retiradas_por_setor[$cont]->retirados }}
                                }
                                @if($cont < sizeof($retiradas_por_setor) - 1)
                                    ,
                                @endif
                            @endfor
                        ]
                    }]
                });
            @endif
        }

        function produtosEmAtraso(idFuncionario) {
            $.get(URL + "/produtos-em-atraso/" + idFuncionario, function(data) {
                let resultado = "";
                if (typeof data == "string") data = $.parseJSON(data);
                data.forEach((linha) => {
                    resultado += 
                    "<tr>" +
                        "<td width = '50%' class = 'text-left px-2'>" + linha.produto + "</td>" +
                        "<td width = '25%' class = 'text-right pr-2'>" + linha.qtd + "</td>" +
                        "<td width = '25%' class = 'text-right pr-2'>" + linha.validade + "</td>" +
                    "</tr>";
                });
                document.getElementById("table-itens-em-atraso-dados").innerHTML = resultado;
                modal("itensEmAtrasoModal", 0);
            });
        }

        function retiradas(idFuncionario) {
            $.get(URL + "/ultimas-retiradas/" + idFuncionario, function(data) {
                let resultado = "";
                if (typeof data == "string") data = $.parseJSON(data);
                data.forEach((linha) => {
                    resultado += 
                    "<tr>" +
                        "<td width = '75%' class = 'text-left px-2'>" + linha.produto + "</td>" +
                        "<td width = '25%' class = 'text-right pr-2'>" + linha.qtd + "</td>" +
                    "</tr>";
                });
                document.getElementById("table-retirados-dados").innerHTML = resultado;
                modal("retiradasListaModal", 0);
            });
        }
    </script>
    @include("modals.itens_em_atraso_modal")
    @include("modals.retiradas_lista_modal")
@endsection