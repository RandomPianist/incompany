@extends("layouts.rel")

@section("content")
    <div class = "report-header">
        <div class = "float-left">
            <img height = "75px" src = "{{ asset('img/logo.png') }}" />
        </div>
        <div class = "float-right">
            <ul class = "m-0">
                <li class = "text-right">
                    <h6 class = "m-0 fw-600">{{ $titulo }}</h6>
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
    <table class = "report-body table table-sm table-bordered table-striped px-5">
        <thead>
            <tr class = "report-row">
                <td width = "70%">
                    @if ($quebra == "setor") Setor @else Colaborador @endif
                </td>
                <td width = "10%" class = "text-right">Quantidade</td>
                <td width = "20%" class = "text-right">Valor</td>
            </tr>
        </thead>
    </table>    
    <div class = "mb-3">
        <table class = "report-body table table-sm table-bordered table-striped">
            <tbody>
                @foreach ($resultado AS $item)
                    <tr class = "report-row">
                        <td width = "70%">{{ $item["grupo"] }}</td>
                        <td width = "10%" class = "text-right"><b>{{ number_format($item["total_qtd"], 0) }}</b></td>
                        <td width = "20%" class = "text-right"><b>R$ {{ number_format($item["total_valor"], 2, ",", ".") }}</b></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class = "line-div"></div>
    <table class = "report-body table table-sm table-bordered table-striped">
        <tbody>
            <tr>
                <td width = "70%">
                    <h5>Totais:</h5>
                </td>
                <td width = "10%" class = "text-right">
                    <h5>{{ number_format($qtd_total, 0) }}</h5>
                </td>
                <td width = "20%" class = "text-right">
                    <h5>R$ {{ number_format($val_total, 2, ",", ".") }}</h5>
                </td>
            </tr>
        </tbody>
    </table>
@endsection