@extends("layouts.rel")

@section("content")
    @foreach ($resultado AS $item)
        <div class = "report-header">
            <div class = "float-left">
                <div>
                    <span>{{ $resultado[0]["empresa"] }}</span>
                </div>
                <div>
                    <span>CNPJ: {{ $resultado[0]["cnpj"] }}</span>
                </div>
            </div>
        </div>
        <div class = "nome-rel">
            <span class = "m-auto">Controle de Entrega e Reposição de Equipamentos de Proteção Individual - E.P.I.</span>
        </div>
        <div class = "d-grid">
            <div class = "c-1">Nome do Funcionário: {{ $item["nome"] }}</div>
            <div class = "c-2">CPF: {{ $item["cpf"] }}</div>
            <div class = "c-3"></div>
            <div class = "c-1">CARGO: {{ $item["funcao"] }}</div>
            <div class = "c-2">CENTRO DE CUSTO: {{ $item["setor"] }}</div>
            <div class = "c-3">DATA ADMISSÃO: {{ date_format(date_create($item["admissao"]), "d/m/Y") }}</div>
        </div>
        <table class = "table table-sm table-bordered table-striped">
            <thead>
                <tr class = "report-row rep-tb-header">
                    <td width = "72%">
                        <span>RECEBIMENTO DE E.P.I</span>
                    </td>
                    <td width = "28%">
                        <span>DEVOLUÇÃO DE E.P.I</span>
                    </td>
                </tr>
            </thead>
        </table>
        <table class = "report-body table table-sm table-bordered table-striped px-5 rep-tb-color-black">
            <thead>
                <tr class = "report-row">
                    <td width = "10%">Data</td>
                    <td width = "23%">E.P.I</td>
                    <td width = "8%">C.A</td>
                    <td width = "9%">Validade do C.A</td>
                    <td width = "8%">Quantidade</td>
                    <td width = "14%">{{ $item["titulo"] }}</td>
                    <td width = "8%">Data</td>
                    <td width = "10%">C.A</td>
                    <td width = "10%">Assinatura</td>
                </tr>
            </thead>
        </table>
        <div class = "mb-3 rep-tb-color-black">
            <table class = "report-body table table-sm table-bordered table-striped">
                <tbody>
                    @foreach ($item["retiradas"] as $retirada)
                        <tr class = "report-row">
                            <td width = "10%">{{ $retirada["data"] }}</td>
                            <td width = "23%">{{ $retirada["produto"] }}</td>
                            <td width = "8%">{{ $retirada["ca"] }}</td>
                            <td width = "9%">{{ $retirada["validade_ca"] != null ? date_format(date_create($retirada["validade_ca"]), "d/m/Y") : "" }}</td>
                            <td width = "8%" class = "text-right">{{ $retirada["qtd"] }}</td>
                            <td width = "14%" class = "text-center">
                                @if ($retirada["biometria"])
                                    <img src = "data:image/jpeg;base64,{{ $retirada["biometria"] }}" style = 'width:40px' />
                                @else
                                    &nbsp;
                                @endif
                            </td>
                            <td width = "8%">&nbsp;</td>
                            <td width = "10%">&nbsp;</td>
                            <td width = "10%">&nbsp;</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class = "line-div"></div>
        <div class = "rep-ret-rodape">
            <p>Declaro para os devidos fins, que recebi gratuitamente os E.P.I.s e/ou uniforme acima descritos e me comprometo:
            <ul>
                <li>Usá-los apenas para a finalidade a que se destinam;</li>
                <li>Responsabilizar-me por sua guarda e conservação;</li>
                <li>Comunicar ao empregador qualquer modificação que os tornem imprópios para o uso;</li>
                <li>Responsabilizar-me pela danificação do E.P.I e/ou uniforme devido ao uso inadequado ou fora das atividades a que se destina, bem como pelo seu extravio;</li>
                <li>Ciente que serei advertido de acordo com o artigo 482 da CLT se não fizer uso devido dos E.P.I e uniformes entregues a mim.</li>
            </ul>
            <p>Declaro estar ciente de que o uso <span class = "bold">é obrigatório</span> enquanto eu estiver exercendo minhas funções.</p>
            <p>Sob pena de ser punido conforme Lei n. 6.514, de 22/12/77, artigo 158</p>
            <p>Declaro, ainda que recebi treinamento e orientação referente ao uso do E.P.I e as Normas de Segurança do Trabalho.</p>
            <div class = "data-extenso">
                <span class = "traduzir">{{ $item["cidade"] }}, {{ $data_extenso }}</span>
            </div>
            <div class = "assinatura">
                <div class = "asn_1">
                    <span>{{ $resultado[0]["empresa"] }}</span>
                </div>
                <div class = "asn_2">
                    <span>{{ $item["nome"] }}</span>
                </div>
            </div>
            <div class = "assinatura-print" style = "margin-top:80px">
                <table class = "table" style = "border-style:border-none">
                    <tr>
                        <td class = "text-center" width = "50%" style = "vertical-align:bottom">________________________________________________</td>
                        <td class = "text-center" width = "50%" style = "vertical-align:bottom">________________________________________________</td>
                    </tr>
                    <tr>
                        <td class = "text-center" width = "50%" style = "vertical-align:top">{{ $resultado[0]["empresa"] }}</td>
                        <td class = "text-center" width = "50%" style = "vertical-align:top">{{ $item["nome"] }}</td>
                    </tr>
                </table>
            </div>
        </div>
        <div class = "pagebreak2"></div>
    @endforeach
@endsection