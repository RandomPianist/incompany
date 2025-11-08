@props(["cadastro", "tipo"])

<div class = "row">
    <div class = "col-12">
        <div class = "custom-control custom-switch">
            <input id = "{{ $cadastro }}-{{ $tipo }}" name = "{{ $tipo }}" type = "hidden" />
            <input id = "{{ $cadastro }}-{{ $tipo }}-chk" class = "checkbox custom-control-input" type = "checkbox" />
            <label id = "{{ $cadastro }}-{{ $tipo }}-lbl" for = "{{ $cadastro }}-{{ $tipo }}-chk" class = "custom-control-label lbl-permissao"></label>
        </div>
    </div>
</div>