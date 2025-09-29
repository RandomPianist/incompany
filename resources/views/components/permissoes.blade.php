@for ($i = 0; $i < sizeof($permissoes); $i++)
    <div class = "row">
        <div class = "col-12">
            <div class = "custom-control custom-switch">
                <input id = "{{ $permissoes[$i] }}" name = "{{ $permissoes[$i] }}" type = "hidden" />
                <input id = "{{ $permissoes[$i] }}-chk" class = "checkbox custom-control-input" type = "checkbox" onchange = "atualizarChk('{{ $permissoes[$i] }}', false)" />
                <label id = "{{ $permissoes[$i] }}-lbl" for = "{{ $permissoes[$i] }}-chk" class = "custom-control-label">
                    Pessoas nesse centro de custo
                    @if ($permissoes[$i] == "financeiro") têm, @else podem, @endif
                    por padrão,
                    @switch ($permissoes[$i])
                        @case ("financeiro")
                            acesso ao módulo financeiro.
                            @break
                        @case ("atribuicoes")
                            atribuir produtos e grades a funcionários.
                            @break
                        @case ("retiradas")
                            fazer retiradas retroativas.
                            @break
                        @case ("supervisor")
                            usar suas senhas para autorizar retiradas de produtos antes do vencimento.
                            @break
                        @case ("solicitacoes")
                            solicitar reposição de produtos.
                            @break
                        @default
                            criar, editar e excluir
                            @if ($permissoes[$i] == "usuarios") usuários, exceto administradores. @else funcionários. @endif
                    @endswitch
                <label>
            </div>
        </div>
    </div>
@endfor