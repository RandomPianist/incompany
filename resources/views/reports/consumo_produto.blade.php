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
            <tr class = "report-row fw-600">
                <td width = "8%">Cód. Externo</td>
                <td width = "40%">Produto</td>
                <td width = "20%">Referência</td>
                <td width = "10%" class="text-right">Quantidade</td>
                <td width = "10%" class="text-right">Valor Unitário</td>
                <td width = "10%" class="text-right">Valor Total</td>
            </tr>
        </thead>
        <tbody>
            @foreach ($resultado as $produto)
                <tr class = "report-row">
                    <td width = "8%">{{ $produto["cod_externo"] }}</td>
                    <td width = "40%">{{ $produto["produto"] }}</td>
                    <td width = "20%">{{ $produto["referencia"] }}</td>
                    <td width = "10%" class = "text-right">{{ number_format($produto["qtd_total"], 0) }}</td>
                    <td width = "10%" class = "text-right">R$ {{ number_format($produto["preco"], 2, ',', '.') }}</td>
                    <td width = "10%" class = "text-right">R$ {{ number_format($produto["valor_total"], 2, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class = "report-body table table-sm table-bordered table-striped mt-4">
        <tbody>
            <tr>
                <td width = "70%">
                    <h5>Total Geral:</h5>
                </td>
                <td width = "10%" class="text-right">
                    <h5>{{ number_format($qtd_total, 0) }}</h5>
                </td>
                <td width = "20%" class="text-right">
                    <h5>R$ {{ number_format($val_total, 2, ',', '.') }}</h5>
                </td>
            </tr>
        </tbody>
    </table>
@endsection