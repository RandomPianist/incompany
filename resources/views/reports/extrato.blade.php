@extends("layouts.rel")

@section("content")
    <div class = "report-header">
        <div class = "float-left">
            <img height = "75px" src = "{{ asset('img/logo.png') }}" />
        </div>
        <div class = "float-right">
            <ul class = "m-0">
                <li class = "text-right">
                    <h6 class = "m-0 fw-600">Extrato de itens</h6>
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
    @foreach ($resultado AS $item)
        <h5>{{ $item["maquina"]["descr"] }}</h5>
        @foreach ($item["maquina"]["produtos"] as $produto)
            <table class = "w-100">
                <tr>
                    <td width = "50%">
                        <h6 class = "pl-3 fw-600">{{ $produto["descr"] }}</h6>
                    </td>
                    <td width = "50%">
                        <h6 class = "pl-3 fw-600">Preço: R$ {{ str_replace(".", ",", $produto["preco"]) }}</h6>
                    </td>
                    <td class = "manter-junto">
                        <h6 class = "pl-3 fw-600">Saldo total: {{ $produto["saldo"] }}</h6>
                    </td>
                </tr>
            </table>
            @if ($lm)
                <h6 class = "pl-3 fw-600">Movimentações do produto</h6>
                <table class = "report-body table table-sm table-bordered table-striped px-5">
                    <thead>
                        <tr class = "report-row">
                            <td width = "20%" class = "text-center">Data</td>
                            <td width = "20%">E/S</td>
                            <td width = "20%" class = "text-right">Quantidade</td>
                            <td width = "40%">Autor</td>
                        </tr>
                    </thead>
                </table>
                <div class = "mb-3">
                    <table class = "report-body table table-sm table-bordered table-striped">
                        <tbody>
                            @foreach ($produto["movimentacao"] as $movimentacao)
                                <tr class = "report-row">
                                    <td width = "20%" class = "text-center">{{ $movimentacao["data"] }}</td>
                                    <td width = "20%">
                                        @if ($movimentacao["es"] == 'E') Entrada @else Saída @endif
                                    </td>
                                    <td width = "20%" class = "text-right">{{ $movimentacao["qtd"] }}</td>
                                    <td width = "40%">{{ $movimentacao["autor"] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class = "line-div"></div>
            @endif
        @endforeach
    @endforeach
@endsection