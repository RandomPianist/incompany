@extends("layouts.rel")

@section("content")
    @foreach ($resultado AS $item)
        <div class="nome-rel" style="margin-top: 10px; margin-bottom: 40px; font-size: 36px; font-weight: bold; text-align: center;">
            <span class="m-auto">TERMO DE CIÊNCIA E AUTORIZAÇÃO PARA USO DE BIOMETRIA (IMPRESSÕES DIGITAIS)</span>
        </div>

        <div class="float-left" style="width: 100%; margin-bottom: 25px; font-size: 20px;">
            <div style="margin-bottom: 5px;">
                <span class="bold">Nome da Empresa/Instituição:</span> {{ $item->razao_social }}
            </div>
            <div style="margin-bottom: 5px;">
                <span class="bold">CNPJ:</span> {{ $item->cnpj }}
            </div>
            <div style="margin-bottom: 5px;">
                <span class="bold">Cidade:</span> {{ $item->cidade }}
            </div>
        </div>

        <div style="margin-bottom: 10px; font-size: 27px;">
            <strong>IDENTIFICAÇÃO DO TITULAR</strong>
        </div>

        <div class="d-grid mb-2" style="grid-row-gap: 10px; font-size: 20px;">
            <div style="grid-column: 1 / -1;">
                Nome completo: {{ $item->nome }}
            </div>

            <div class="c-1" style="width: 100%; display: flex; justify-content: start;">
                <span class="mr-2">CPF: {{ $item->cpf }}</span>
            </div>

            <div class="c-2">
                <span>
                    @if ($item->matricula)
                        Matrícula: {{ $item->matricula }}
                    @elseif ($item->rg)
                        RG: {{ $item->rg }}
                    @else
                        Matrícula: __________________________
                    @endif

                    <!-- @if ($item->id == 83 || $item->id == 343 || $item->id == 344 || $item->id == 345 || $item->id == 347)
                        RG:
                    @else
                        Matrícula:
                    @endif
                        {{ $item->matricula ?? "__________________________" }} -->
                </span>
            </div>
        </div>

        <div class="rep-ret-rodape" style="padding-top: 30px; text-align: justify; margin-bottom: 0.125rem; font-size: 20px;">
            <p style="font-size: 27px;"><strong>OBJETO DO TERMO</strong></p>
            <p>Declaro, para os devidos fins, que:</p>
            <ol>
                <li>Fui informado(a) de que a coleta das <strong>minhas impressões digitais</strong> será utilizada
                    exclusivamente para fins de <strong>[ex.: controle de acesso, registro de frequência, segurança
                    interna, autenticação biométrica etc.]</strong>, em conformidade com a legislação vigente,
                    especialmente a <strong>Lei Geral de Proteção de Dados Pessoais (Lei nº 13.709/2018 – LGPD)</strong>.</li>

                <li>Estou ciente de que meus dados biométricos são considerados <strong>dados pessoais sensíveis</strong> e
                    serão tratados com segurança e confidencialidade, sem compartilhamento com terceiros, salvo
                    obrigação legal ou ordem judicial.</li>

                <li>Sei que posso solicitar a exclusão dos meus dados biométricos caso deixe de manter vínculo
                    com a <strong>[empresa/instituição]</strong>, respeitados os prazos legais.</li>
            </ol>

            <p style="font-size: 27px; padding-top: 30px;"><strong>DECLARAÇÃO DE CIÊNCIA</strong></p>
            <p>Confirmo que recebi todas as informações necessárias, compreendi o objetivo da coleta e autorizo,
                de forma livre, informada e inequívoca, o uso das minhas impressões digitais conforme descrito acima.</p>
        </div>

        <div class="pagebreak2"></div>

        <div style="margin-top: 70px;">
            <p style="font-size: 27px;"><strong>COLETA DAS DIGITAIS</strong></p>
            
            <table class="report-body table-sm table-bordered table-striped px-5 rep-tb-color-black align-items-center">
                <thead>
                    <tr style="height: 50px; background-color: #cacacaff;">
                        <td width="70%" style="text-align: left; vertical-align: middle; font-weight: bold; font-size: 18px;">Dedo</td>
                        <td width="30%" style="text-align: center; vertical-align: middle; font-weight: bold; font-size: 18px;data_extenso">Digital</td>
                    </tr>
                </thead>
                <tbody>
                    @for ($i = 0; $i < sizeof($dedos_nome); $i++)
                        <tr>
                            <td style="text-align: left; vertical-align: middle;">{{ $dedos_nome[$i] }}</td>
                            <td style="height: 50px; text-align: center;">
                                @if ($item->dedos[$i])
                                    <img src = "data:image/jpeg;base64,{{ $item->dedos[$i] }}" style = "width:40px;" />
                                @else
                                    &nbsp;
                                @endif
                            </td>
                        </tr>
                    @endfor
                </tbody>
            </table>
        </div>

        <div class="rep-ret-rodape" style="margin-top: 50px; page-break-inside: avoid; font-size: 19px;">
            <div style="margin-bottom: 70px;">
                <span class = "traduzir"> Local e data: {{ $item->cidade }}, {{ $data_extenso }}</span>
            </div>

            <div style="margin-bottom: 70px;">
                <span>Assinatura do Titular:</span> ______________________________________________________________________
            </div>

            <div style="margin-bottom: 70px;">
                <span>Assinatura do Responsável pela Coleta:</span> ___________________________________________________
            </div>

            <div>
                <div style=" margin-bottom: 5px;">Carimbo da Empresa/Instituição (se aplicável):</div>
            </div>
        </div>

        @if (! $loop->last)
            <div class="pagebreak2"></div>
        @endif
    @endforeach
@endsection