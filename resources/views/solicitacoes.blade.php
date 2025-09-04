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
    <form action = "{{ $root_url }}/solicitacoes/criar" method = "POST" class = "d-none">
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
                            @if (request("tipo") == "G") Giro de estoque @else Qtde. mínima @endif
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
                                    @if ($produto["entradas"])
                                        <i
                                            class = "my-icon fal fa-eye"
                                            title = "Detalhar"
                                            onclick = "detalhar('E', {{ $produto['id'] }})"
                                        ></i>
                                    @endif
                                </td>
                                <td width = "8%" class = "text-right">
                                    {{ $produto["saidas_avulsas"] }}
                                    @if ($produto["saidas_avulsas"])
                                        <i
                                            class = "my-icon fal fa-eye"
                                            title = "Detalhar"
                                            onclick = "detalhar('S', {{ $produto['id'] }})"
                                        ></i>
                                    @endif
                                </td>
                                <td width = "8%" class = "text-right">
                                    {{ $produto["retiradas"] }}
                                    @if ($produto["retiradas"])
                                        <i
                                            class = "my-icon fal fa-eye"
                                            title = "Detalhar"
                                            onclick = "detalhar('R', {{ $produto['id'] }})"
                                        ></i>
                                    @endif
                                </td>
                                <td width = "8%" class = "text-right">{{ $produto["saidas_totais"] }}</td>
                                <td width = "8%" class = "text-right">{{ $produto["saldo_res"] }}</td>
                                <td width = "8%" class = "text-right">
                                    @if (request("tipo") == "G") {{ $produto["giro"] }} @else {{ $produto["minimo"] }} @endif
                                </td>
                                <td width = "8%" class = "text-right sugerido">{{ $produto["sugeridos"] }}</td>
                                <td width = "8%" class = "text-center">
                                    <i
                                        class = "my-icon fal fa-minus"
                                        onclick = "calcular(this, -1)"
                                        @if (!$produto["sugeridos"])
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
        const URL = "{{ $root_url }}";
        const INICIO = "{{ request('inicio') }}";
        const FIM = "{{ request('fim') }}";
    </script>
    <script type = "text/javascript" language = "JavaScript" src = "{{ asset('js/lib/sweetalert2.js')         }}"></script>
    <script type = "text/javascript" language = "JavaScript" src = "{{ asset('js/geral/alerta.js')            }}"></script>
    <script type = "text/javascript" language = "JavaScript" src = "{{ asset('js/especifico/solicitacoes.js') }}"></script>
@endsection