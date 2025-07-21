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

            <div class = "card-dashboard mx-1 bg-white rounded-lg shadow-sm custom-scrollbar" id = "card-retiradas-centro">
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
    
    <div class = "loader-container">
        <div class = "loader"></div>
    </div>

    <style>
        .loader-container {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 3;
            background: #DDD
        }

        .loader {
            border: 8px solid #f3f3f3; /* Light grey */
            border-top: 8px solid #3498db; /* Blue */
            border-radius: 50%;
            width: 120px;
            height: 120px;
            animation: spin 1.5s linear infinite;
            margin: auto;
            margin-top: calc(25% - 60px)
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>

    <script type="text/javascript" language="JavaScript">
        async function gerarSelect() {
            const selectElement = document.getElementById('dashboard-select');
            const meses = [
                'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
                'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'
            ];

            const dataAtual = new Date(); 
            let mesAtual = dataAtual.getMonth(); 
            let anoAtual = dataAtual.getFullYear(); 
            let option;
            for (let i = 0; i < 12; i++) {
                option = document.createElement('option');

                if (mesAtual < 0) {
                    mesAtual = 11; // Dezembro
                    anoAtual--;
                }

                const ultimoDia = new Date(anoAtual, mesAtual + 1, 0).getDate();

                option.value = `${anoAtual}-${mesAtual + 1}-1 ${anoAtual}-${mesAtual + 1}-${ultimoDia}`;
                option.textContent = `${meses[mesAtual]} de ${anoAtual}`;
                
                const dataInicial = option.value.split(" ")[0];
                const dataFinal = option.value.split(" ")[1];
                resp = await $.get(URL + "/dashboard/maquinas", {
                    inicio: dataInicial,
                    fim: dataFinal
                });
                const parsedResp = JSON.parse(resp);
                if (parsedResp.length) {
                    selectElement.appendChild(option);
                    mesAtual--;
                } else i = 12;
            }
            selectElement.appendChild(option);
        }

        function getMesSelecionado() {
            const selectElement = document.getElementById('dashboard-select');
            const date = selectElement.value;
            return date;
        }

        function formatarData(dataISO) {
            const [ano, mes, dia] = dataISO.split("-"); // Divide a data em partes
            return `${dia}/${mes}/${ano}`; // Rearranja no formato DD/MM/YYYY
        }

        function extrato_maquina_dashboard(id_maquina) {
            let req = {};
            req.id_produto = "";
            const dataInicial = getMesSelecionado().split(" ")[0];
            const dataFinal = getMesSelecionado().split(" ")[1];

            const dataInicialFormatada = formatarData(dataInicial); // 01/01/2025
            const dataFinalFormatada = formatarData(dataFinal); // 31/01/2025

            req.inicio = dataInicialFormatada;
            req.fim = dataFinalFormatada;

            req.lm = "S";
            req.id_maquina = id_maquina;
            let link = document.createElement("a");
            link.href = URL + "/relatorios/extrato?" + $.param(req);
            link.target = "_blank";
            link.click();
        }

        async function getDadosCards() {
            document.querySelector(".loader-container").classList.remove("d-none");

            const dataSelecionada = getMesSelecionado();
            const dataInicial = dataSelecionada.split(" ")[0];
            const dataFinal = dataSelecionada.split(" ")[1];

            const resp = await $.get(URL + "/dashboard/dados", {
                inicio: dataInicial,
                fim: dataFinal
            });
            const parsedResp = JSON.parse(resp);
            // console.log(parsedResp);

            const cardUltimaRetirada = document.getElementById("card-ult-retirada-dados");
            const cardRetiradaAtraso = document.getElementById("card-retirada-atraso-dados");
            const cardRetiradaCentro = document.getElementById("card-retiradas-centro-dados");
            const cardConsumosCentro = document.getElementById("card-cons-centro-dados");
            const cardRetiradasColab = document.getElementById("card-retiradas-colab-dados");
            const cardMinhasMaquinas = document.getElementById("card-minhas-maquinas-dados");

            const mesAtual = (new Date()).getMonth();
            const mesSelecionado = dataInicial.split("-")[1] - 1;
            const cardRetiradaAtrasoElement = document.getElementById("card-retirada-atraso");
            const cardUltimasRetiradasElement = document.getElementById("card-ult-retirada");

            if(mesAtual !== mesSelecionado) {
                cardRetiradaAtrasoElement.style.display = "none";
                cardUltimasRetiradasElement.style.display = "none";
            } else {
                cardRetiradaAtrasoElement.style.display = "block";
                cardUltimasRetiradasElement.style.display = "block";
            }

            cardUltimaRetirada.innerHTML = "";
            cardRetiradaAtraso.innerHTML = "";
            cardRetiradaCentro.innerHTML = "";
            cardConsumosCentro.innerHTML = "";
            cardRetiradasColab.innerHTML = "";
            cardMinhasMaquinas.innerHTML = "";

            let ultimaRetiradaHTML = "";
            let retiradaAtrasoHTML = "";
            let retiradaCentroHTML = "";
            let consumosCentroHTML = "";
            let retiradasColabHTML = "";
            let minhasMaquinasHTML = "";

            const ultimasRetiradas  = parsedResp.ultimasRetiradas;
            const atrasos           = parsedResp.atrasos;
            const retiradasPorSetor = parsedResp.retiradasPorSetor;
            const ranking           = parsedResp.ranking;
            const maquinas          = parsedResp.maquinas;

            if(ultimasRetiradas.length > 0) {
                ultimaRetiradaHTML += 
                    `<div class = "d-flex justify-content-center">
                        <table class = "table table-body-dashboard clickable">`;

                ultimasRetiradas.forEach(item => {
                    ultimaRetiradaHTML +=
                    `<tr onclick = "ultimasRetiradas(${item.id}, '${item.nome}')">
                         <td width = "20%" class = "td-foto text-center">
                             <img class = 'foto-funcionario-dashboard' src = '${item.foto}'
                                 onerror = "this.classList.add('d-none');this.nextElementSibling.classList.remove('d-none')" />
                             <i class = 'fas fa-user d-none'></i>
                         </td>
                         <td width="80%" class = "td-nome">${item.nome}</td>
                     </tr>`;
                });

                ultimaRetiradaHTML += `</div>
                                   </table>`;
            } else {
                ultimaRetiradaHTML = 
                   `<div class = "d-flex flex-column align-items-center m-5">
                        <span>Não há nada a mostrar</span>
                    </div>`;
            }

            if(atrasos.length > 0) {
                retiradaAtrasoHTML += 
                    `<div class = "d-flex justify-content-center">
                        <table class = "table table-body-dashboard clickable">`;

                atrasos.forEach(item => {
                    retiradaAtrasoHTML +=
                    `<tr onclick = "produtosEmAtraso(${item.id}, '${item.nome}')">
                         <td width = "20%" class = "td-foto text-center">
                             <img class = 'foto-funcionario-dashboard' src = '${item.foto}'
                                 onerror = "this.classList.add('d-none');this.nextElementSibling.classList.remove('d-none')" />
                             <i class = 'fas fa-user d-none'></i>
                         </td>
                         <td width="75%" class = "td-nome">${item.nome}</td>
                         <td class="text-right" width = "5%">
                             <div class = "numerico">
                                <span>${item.total}</span>
                             </div>
                         </td>
                     </tr>`;
                });

                retiradaAtrasoHTML += `</div>
                                   </table>`;
            } else {
                retiradaAtrasoHTML = 
                   `<div class = "d-flex flex-column align-items-center m-5">
                        <span>Não há nada a mostrar</span>
                    </div>`;
            }

            if(retiradasPorSetor.retiradas.length > 0) {
                retiradaCentroHTML +=
                    `<figure class="highcharts-figure">
                        <div id="container"></div>
                    </figure>`;
                cardRetiradaCentro.innerHTML += retiradaCentroHTML;
                gerarGraficoRetiradasCentro(retiradasPorSetor);
            } else {
                retiradaCentroHTML = 
                   `<div class = "d-flex flex-column align-items-center m-5">
                        <span>Não há nada a mostrar</span>
                    </div>`;
                cardRetiradaCentro.innerHTML += retiradaCentroHTML;
            }

            if(retiradasPorSetor.retiradas.length > 0) {
                consumosCentroHTML +=
                    `<div class = "d-flex justify-content-center">
                        <table class = "table table-body-dashboard clickable">`;

                retiradasPorSetor.retiradas.forEach(item => {
                    consumosCentroHTML +=
                    `<tr onclick="retiradasSetor(${item.id}, '${item.descr}')">
                         <td width="65%" class = "pl-4">${item.descr}</td>
                         <td class="text-right" width = "35%">
                             R$ ${Number(item.valor).toFixed(2).toString().replace(".", ",")}
                         </td>
                     </tr>`;
                });

                consumosCentroHTML +=
                    `<tr>
                         <td width="65%" class = "pl-4"><strong>Total</strong></td>
                         <td class="text-right" width = "35%"><strong>
                             R$ ${Number(retiradasPorSetor.totalVal).toFixed(2).toString().replace(".", ",")}
                         </strong></td>
                     </tr>`;

                consumosCentroHTML += `</div>
                                   </table>`;
            } else {
                consumosCentroHTML = 
                   `<div class = "d-flex flex-column align-items-center m-5">
                        <span>Não há nada a mostrar</span>
                    </div>`;
            }

            if(ranking.length > 0) {
                retiradasColabHTML += 
                    `<div class = "d-flex justify-content-center">
                        <table class = "table table-body-dashboard clickable">`;

                ranking.forEach(item => {
                    retiradasColabHTML +=
                    `<tr onclick = "retiradas(${item.id}, '${item.nome}')">
                         <td width = "20%" class = "td-foto text-center">
                             <img class = 'foto-funcionario-dashboard' src = '${item.foto}'
                                 onerror = "this.classList.add('d-none');this.nextElementSibling.classList.remove('d-none')" />
                             <i class = 'fas fa-user d-none'></i>
                         </td>
                         <td width="75%" class = "td-nome">${item.nome}</td>
                         <td class="text-right" width = "5%">
                             <div class = "numerico">
                                <span>${parseInt(item.retirados)}</span>
                             </div>
                         </td>
                     </tr>`;
                });

                retiradasColabHTML += `</div>
                                   </table>`;
            } else {
                retiradasColabHTML = 
                   `<div class = "d-flex flex-column align-items-center m-5">
                        <span>Não há nada a mostrar</span>
                    </div>`;
            }

            if(maquinas.length > 0) {
                minhasMaquinasHTML +=
                    `<div class = "d-flex justify-content-center">
                        <table class = "table table-body-dashboard clickable">`;
                        
                maquinas.forEach(item => {
                    minhasMaquinasHTML +=
                    `<tr onclick = "extrato_maquina_dashboard(${item.id})">
                         <td width = "20%" class = "td-foto text-center">
                             <img class = 'foto-funcionario-dashboard'
                                 src = '${URL + "/img/maquinas.png"}' />
                         </td>
                         <td width = "80%" class = "td-nome">${item.descr}</td>
                     </tr>
                     `;
                });

                minhasMaquinasHTML += `</div>
                                   </table>`;
            } else {
                minhasMaquinasHTML = 
                   `<div class = "d-flex flex-column align-items-center m-5">
                        <span>Não há nada a mostrar</span>
                    </div>`;
            }

            cardUltimaRetirada.innerHTML += ultimaRetiradaHTML;
            cardRetiradaAtraso.innerHTML += retiradaAtrasoHTML; 
            cardConsumosCentro.innerHTML += consumosCentroHTML; 
            cardRetiradasColab.innerHTML += retiradasColabHTML; 
            cardMinhasMaquinas.innerHTML += minhasMaquinasHTML;

            document.querySelector(".loader-container").classList.add("d-none");
        }

        function listar() {
            gerarSelect();
            getDadosCards();
        }

        function gerarGraficoRetiradasCentro(retiradasPorSetor) {
            const retiradas = retiradasPorSetor.retiradas;
            const total = retiradasPorSetor.totalQtd;

            const transformedArray = retiradas.map(item => ({
                name: item.descr,
                y: item.retirados
            }));

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
                                        `Total<br/>
                                        <strong>${total}</strong>`
                                    )
                                    .css({
                                        color: '#000',
                                        textAnchor: 'middle'
                                    })
                                    .add();
                            }

                            const x = series.center[0] + chart.plotLeft,
                                y = series.center[1] + chart.plotTop - (customLabel.attr('height') / 2);

                            customLabel.attr({
                                x,
                                y
                            });
                            // Set font size based on chart diameter
                            customLabel.css({
                                fontSize: `${series.center[2] / 12}px`
                            });
                            document.querySelector(".highcharts-figure #container > div").style.height = "300px";
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
                    data: transformedArray
                }]
            });
        }

        function produtosEmAtraso(idFuncionario, nome) {
            $.get(URL + "/dashboard/retiradas-em-atraso/" + idFuncionario, function(data) {
                let resultado = "";
                if (typeof data == "string") data = $.parseJSON(data);
                data.forEach((linha) => {
                    resultado +=
                        "<tr>" +
                            "<td width = '60%'>" + linha.produto + "</td>" +
                            "<td width = '20%' class = 'text-right'>" + linha.qtd + "</td>" +
                            "<td width = '20%' class = 'text-right'>" + linha.validade + "</td>" +
                        "</tr>";
                });
                document.getElementById("itensEmAtrasoModalLabel").innerHTML = `Retiradas em atraso (${nome})`; 
                document.getElementById("table-itens-em-atraso-dados").innerHTML = resultado;
                modal("itensEmAtrasoModal", 0);
            });
        }

        function ultimasRetiradas(idFuncionario, nome) {
            const dataSelecionada = getMesSelecionado();
            const dataInicial = dataSelecionada.split(" ")[0];
            const dataFinal = dataSelecionada.split(" ")[1];

            $.get(URL + "/dashboard/ultimas-retiradas", {
                id_pessoa: idFuncionario,
                inicio: dataInicial,
                fim: dataFinal
            }, function(data) {
                let resultado = "";
                if (typeof data == "string") data = $.parseJSON(data);
                data.forEach((linha) => {
                    resultado +=
                        "<tr>" +
                            "<td width = '75%'>" + linha.produto + "</td>" +
                            "<td width = '20%'>" + linha.data + "</td>" +
                            "<td width = '10%' class = 'text-right'>" + linha.qtd + "</td>" +
                        "</tr>";
                });
                document.getElementById("ultimasRetiradasModalLabel").innerHTML = `Últimas retiradas (${nome})`; 
                document.getElementById("table-ultimas-retiradas-dados").innerHTML = resultado;
                modal("ultimasRetiradasModal", 0);
            });
        }

        function retiradas(idFuncionario, nome) {
            const dataSelecionada = getMesSelecionado();
            const dataInicial = dataSelecionada.split(" ")[0];
            const dataFinal = dataSelecionada.split(" ")[1];

            $.get(URL + "/dashboard/retiradas-por-pessoa", {
                id_pessoa: idFuncionario,
                inicio: dataInicial,
                fim: dataFinal
            }, function(data) {
                let resultado = "";
                if (typeof data == "string") data = $.parseJSON(data);
                data.forEach((linha) => {
                    resultado +=
                        "<tr>" +
                            "<td width = '80%'>" + linha.produto + "</td>" +
                            "<td width = '20%' class = 'text-right'>" + linha.qtd + "</td>" +
                        "</tr>";
                });
                document.getElementById("retiradasListaModalLabel").innerHTML = `Retiradas do colaborador (${nome})`; 
                document.getElementById("table-retirados-dados").innerHTML = resultado;
                modal("retiradasListaModal", 0);
            });
        }

        function retiradasSetor(idSetor, nome) {
            const dataSelecionada = getMesSelecionado();
            const dataInicial = dataSelecionada.split(" ")[0];
            const dataFinal = dataSelecionada.split(" ")[1];

            $.get(URL + "/dashboard/retiradas-por-setor", {
                id_setor: idSetor,
                inicio: dataInicial,
                fim: dataFinal
            }, function(data) {
                let resultado = "";
                if (typeof data == "string") data = $.parseJSON(data);
                data.forEach((linha) => {
                    resultado +=
                        "<tr>" +
                            "<td width = '75%'>" + linha.nome + "</td>" +
                            "<td width = '25%'>R$ " + Number(linha.valor).toFixed(2).toString().replace(".", ",") + "</td>" +
                        "</tr>";
                });
                document.getElementById("retiradasCentroModalLabel").innerHTML = `Consumo do centro de custo (${nome})`;
                document.getElementById("table-retirados-centro-dados").innerHTML = resultado;
                modal("retiradasCentroModal", 0);
            });
        }
    </script>
    @include('modals.dashboard.itens_em_atraso_modal')
    @include('modals.dashboard.ultimas_retiradas_modal')
    @include('modals.dashboard.retiradas_lista_modal')
    @include('modals.dashboard.retiradas_centro_modal')
@endsection
