@props(["cadastro"])
@props(["permissao"])

<div class = "row">
    <div class = "col-12">
        <div class = "custom-control custom-switch">
            <input id = "{{ $cadastro }}-{{ $permissao }}" name = "{{ $permissao }}" type = "hidden" />
            <input id = "{{ $cadastro }}-{{ $permissao }}-chk" class = "checkbox custom-control-input" type = "checkbox" />
            <label id = "{{ $cadastro }}-{{ $permissao }}-lbl" for = "{{ $cadastro }}-{{ $permissao }}-chk" class = "custom-control-label lbl-permissao">
                @if ($permissao == "visitante") 
                    @if ($cadastro == "pessoa")
                        Essa pessoa é um visitante.
                    @else
                        Pessoas nesse centro de custo são, por padrão, visitantes.
                    @endif
                @endif
            </label>
        </div>
    </div>
</div>