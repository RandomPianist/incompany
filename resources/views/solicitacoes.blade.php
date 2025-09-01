@extends("layouts.rel")

@section("content")
    <div class = "report-header">
        <div class = "float-left">
            <img height = "75px" src = "{{ asset('img/logo.png') }}" />
        </div>
        <div class = "float-right">
            <ul class = "m-0">
                <li class = "text-right">
                    <h6 class = "m-0 fw-600">Solicitação de compra</h6>
                </li>
                <li class = "text-right">
                    <h6 class = "m-0 traduzir">
                        @php
                            date_default_timezone_set("America/Sao_Paulo");
                            echo ucfirst(strftime("%A, %d de %B de %Y"));
                        @endphp
                    </h6>
                </li>
                <li class = "text-right">
                    @if ($criterios)
                        <h6 class = "m-0">Critérios:</h6>
                        <small>{{ $criterios }}</small>
                    @endif
                </li>
            </ul>
        </div>
    </div>
    <div class = "mt-2 mb-3 linha"></div>
    <form action = "{{ config('app.root_url') }}/solicitacoes/criar" method = "POST" class = "d-none">
        @csrf
        @foreach ($resultado AS $item)
            <input type = "hidden" name = "id_comodato" id = "id_comodato" />
            <input type = "hidden" id = "id_maquina" value = "{{ $item['maquina']['id'] }}" />
            <h5>{{ $item["maquina"]["descr"] }}</h5>
            <table class = "report-body table table-sm table-bordered table-striped px-5">
                <thead>
                    <tr class = "report-row">
                        <td width = "28%">Produto</td>
                        <td width = "8%" class = "text-right">Saldo anterior</td>
                        <td width = "8%" class = "text-right">Entradas</td>
                        <td width = "8%" class = "text-right">Saídas avulsas</td>
                        <td width = "8%" class = "text-right">Retiradas</td>
                        <td width = "8%" class = "text-right">Saídas totais</td>
                        <td width = "8%" class = "text-right">Saldo final</td>
                        <td width = "8%" class = "text-right">
                            @if ($mostrar_giro) Giro de estoque @else Qtde. mínima @endif
                        </td>
                        <td width = "8%" class = "text-right">Qtde. Sugerida</td>
                        <td width = "8%" class = "text-center">Solicitar</td>
                    </tr>
                </thead>
            </table>
            <div class = "mb-3">
                <table class = "report-body table table-sm table-bordered table-striped">
                    <tbody>
                        @foreach ($item["maquina"]["produtos"] as $produto)
                            <tr class = "report-row" id = "produto-{{ $produto['id'] }}">
                                <td width = "28%">{{ $produto["descr"] }}</td>
                                <td width = "8%" class = "text-right">{{ $produto["saldo_ant"] }}</td>
                                <td width = "8%" class = "text-right">
                                    {{ $produto["entradas"] }}
                                    @if ($produto["entradas"] > 0)
                                        <i
                                            class = "my-icon fal fa-eye"
                                            title = "Detalhar"
                                            onclick = "detalhar('E', {{ $produto['id'] }})"
                                        ></i>
                                    @endif
                                </td>
                                <td width = "8%" class = "text-right">
                                    {{ $produto["saidas_avulsas"] }}
                                    @if ($produto["saidas_avulsas"] > 0)
                                        <i
                                            class = "my-icon fal fa-eye"
                                            title = "Detalhar"
                                            onclick = "detalhar('S', {{ $produto['id'] }})"
                                        ></i>
                                    @endif
                                </td>
                                <td width = "8%" class = "text-right">
                                    {{ $produto["retiradas"] }}
                                    @if ($produto["retiradas"] > 0)
                                        <i
                                            class = "my-icon fal fa-eye"
                                            title = "Detalhar"
                                            onclick = "detalhar('R', {{ $produto['id'] }})"
                                        ></i>
                                    @endif
                                </td>
                                <td width = "8%" class = "text-right">{{ $produto["saidas_totais"] }}</td>
                                <td width = "8%" class = "text-right">{{ $produto["saldo_res"] }}</td>
                                @if ($mostrar_giro)
                                    <td width = "8%" class = "text-right">{{ $produto["giro"] }}</td>
                                @else
                                    <td width = "8%" class = "text-right">{{ $produto["minimo"] }}</td>
                                @endif
                                <td width = "8%" class = "text-right sugerido">{{ $produto["sugeridos"] }}</td>
                                <td width = "8%" class = "text-center">
                                    <i
                                        class = "my-icon fal fa-minus"
                                        onclick = "calcular(this, -1)"
                                        @if ($produto["sugeridos"] == 0)
                                            style = "visibility:hidden"
                                        @endif
                                    ></i>
                                    <span class = "solicitado">{{ $produto["sugeridos"] }}</span>
                                    <i class = "my-icon fal fa-plus" onclick = "calcular(this, 1)"></i>
                                    <input type = "hidden" class = "produto" name = "id_produto[]" value = "{{ $produto['id'] }}" />
                                    <input type = "hidden" class = "qtd" name = "qtd[]" value = "{{ $produto['sugeridos'] }}" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach
    </form>

    <script type = "text/javascript" language = "JavaScript">
        function recalcular() {
            $("tbody .report-row").each(function() {
                let qtd = $(this).find(".sugerido").html();
                $($(this).find(".solicitado")[0]).html(qtd);
                $($(this).find(".qtd")[0]).val(qtd);
                let estilo = linha.querySelector(".fa-minus").style;
                if (!parseInt(qtd)) estilo.visibility = "hidden";
                else estilo.removeProperty("visibility");
            });
            
            $.post(URL + "/previas/excluir", {
                _token : $("meta[name='csrf-token']").attr("content"),
                id_comodato : $("#id_comodato").val()
            });
        }

        function calcular(el, val) {
            val += parseInt($($($(el).parent()).find(".qtd")[0]).val());
            let estilo = $($(el).parent()).find(".fa-minus").style;
            if (!val) estilo.visibility = "hidden";
            else estilo.removeProperty("visibility");
            $($($(el).parent()).find(".qtd")[0]).val(val);
            $($($(el).parent()).find(".solicitado")[0]).html(val);
            $.post(URL + "/previas/salvar", {
                _token : $("meta[name='csrf-token']").attr("content"),
                id_comodato : $("#id_comodato").val(),
                id_produto : $($($(el).parent()).find(".produto")[0]).val(),
                qtd : val
            });
        }

        function detalhar(_tipo, _id_produto) {
            $.get(URL + "/solicitacoes/mostrar", {
                id_produto : _id_produto,
                tipo : _tipo,
                id_maquina : $("#id_maquina").val(),
                inicio : "{{ request('inicio') }}",
                fim : "{{ request('fim') }}"
            }, function(data) {
                if (typeof data == "string") data = $.parseJSON(data);
                let supervisor = false;
                let autor = false;
                let origem = false;
                let resultado = "<table class = 'report-body table table-sm table-bordered table-striped px-5'>" +
                    "<thead>" +
                        "<tr class = 'report-row'>" +
                            (_tipo == "R" ?
                                "<td width = '28%' class = 'text-left'>Funcionário</td>" +
                                "<td width = '27%' class = 'text-left supervisor'>Supervisor</td>" +
                                "<td width = '27%' class = 'text-left autor'>Autor</td>" +
                                "<td width = '10%'>Data</td>" +
                                "<td width = '8%' class = 'text-right'>Qtde.</td>"
                            :
                                "<td width = '82%' class = 'text-left origem'>Origem</td>" +
                                "<td width = '10%'>Data</td>" +
                                "<td width = '8%' class = 'text-right'>Qtde.</td>"
                            ) +
                        "</tr>" +
                    "</thead>" +
                "</table>" +
                "<div class = 'mb-3'>" +
                    "<table class = 'report-body table table-sm table-bordered table-striped'>" +
                        "<tbody>";
                data.forEach((linha) => {
                    if (linha.supervisor) supervisor = true;
                    if (linha.autor) autor = true;
                    if (linha.origem) origem = true;
                    resultado += "<tr class = 'report-row'>" +
                        (_tipo == "R" ?
                            "<td width = '28%' class = 'text-left'>" + linha.funcionario + "</td>" +
                            "<td width = '27%' class = 'text-left supervisor'>" + linha.supervisor + "</td>" +
                            "<td width = '27%' class = 'text-left autor'>" + linha.autor + "</td>" +
                            "<td width = '10%'>" + linha.data + "</td>" +
                            "<td width = '8%' class = 'text-right'>" + linha.qtd + "</td>"
                        :
                            "<td width = '82%' class = 'text-left origem'>" + linha.origem + "</td>" +
                            "<td width = '10%'>" + linha.data + "</td>" +
                            "<td width = '8%' class = 'text-right'>" + linha.qtd + "</td>"
                        ) +
                    "</tr>";
                });
                resultado += "</tbody></table></div>";
                switch (_tipo) {
                    case "E":
                        var titulo = "Entradas";
                        break;
                    case "S":
                        var titulo = "Saídas avulsas";
                        break;
                    case "R":
                        var titulo = "Retiradas";
                        break;
                }
                Swal.fire({
                    title : titulo,
                    html : resultado,
                    confirmButtonColor : "rgb(31, 41, 55)"
                });
                if (!autor) {
                    $(".autor").each(function() {
                        $(this).css("display", "none");
                    });
                }
                if (!supervisor) {
                    $(".supervisor").each(function() {
                        $(this).css("display", "none");
                    });
                }
                if (!origem) {
                    $(".origem").each(function() {
                        $(this).css("display", "none");
                    });
                }
            })
        }

        function solicitar() {
            let total = 0;
            $(".qtd").each(function() {
                total += parseInt($(this).val());
            });
            if (!total) {
                Swal.fire({
                    icon : "warning",
                    title : "Atenção",
                    html : "Nenhum item foi solicitado",
                    confirmButtonColor : "rgb(31, 41, 55)"
                });
                return;
            }
            $.get(URL + "/solicitacoes/consultar/" + $("#id_comodato").val(), function(data) {
                if (typeof data == "string") data = $.parseJSON(data);
                if (!parseInt(data.continuar)) {
                    if (parseInt(data.sou_autor) && data.status == "A") {
                        Swal.fire({
                            icon : "warning",
                            title: "Aviso",
                            html : "Já há uma solicitação em aberto, feita no dia " + data.data + ", para a mesma máquina.<br>Gostaria de cancelar a última solicitação feita e sobrescrever por essa?",
                            showDenyButton : true,
                            confirmButtonText : "NÃO",
                            confirmButtonColor : "rgb(31, 41, 55)",
                            denyButtonText : "SIM"
                        }).then((result) => {
                            if (result.isDenied) {
                                $.post(URL + "/solicitacoes/cancelar", {
                                    _token : $("meta[name='csrf-token']").attr("content"),
                                    id : data.id
                                }, function() {
                                    $($("form")[0]).submit();
                                })
                            }
                        });
                    } else {
                        if (!parseInt(data.sou_autor)) {
                            var texto = "Há uma solicitação em " + (data.status == "A" ? "aberto" : "andamento") + ", feita por " + data.autor + " no dia " + data.data + ", para a mesma máquina";
                            if (data.status == "A") texto += ".<br />Entre em contato com " + data.autor + " para cancelá-la.";
                        } else var texto = "A solicitação que você fez no dia " + data.data + " já está em andamento e não é possível cancelá-la";
                        Swal.fire({
                            icon : "warning",
                            title : "Atenção",
                            html : texto,
                            confirmButtonColor : "rgb(31, 41, 55)"
                        });
                    }
                } else $($("form")[0]).submit();
            })
        }
        
        async function carregar() {
            let data = await $.get(URL + "/solicitacoes/meus-comodatos?id_maquina=" + $("#id_maquina").val());
            if (typeof data == "string") data = $.parseJSON(data);
            $("#id_comodato").val(data[0]);
            let _produtos = new Array();
            $("tbody .report-row").each(function() {
                _produtos.push($($(this).find(".produto")[0]).val());
            });
            let resp = await $.get(URL + "/previas/preencher", {
                id_comodato : data[0],
                produtos : _produtos.join(",")
            });
            resp = $.parseJSON(resp);
            for (let i = 0; i < resp.length; i++) {
                let qtd = parseInt(resp[i].qtd);                
                $($("#produto-" + resp[i].produto).find(".qtd")[0]).val(qtd);
                $($("#produto-" + resp[i].produto).find(".solicitado")[0]).html(qtd);
                let estilo = pai.querySelector(".fa-minus").style;
                if (!qtd) estilo.visibility = "hidden";
                else estilo.removeProperty("visibility");
            }
            $($("form")[0]).removeClass("d-none");
        }
    </script>
@endsection