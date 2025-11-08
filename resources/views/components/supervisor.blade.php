@props(["cadastro"])

<div class = "row">
    <div class = "col-12">
        <div class = "custom-control custom-switch">
            <input id = "{{ $cadastro }}-supervisor" name = "supervisor" type = "hidden" />
            <input id = "{{ $cadastro }}-supervisor-chk" class = "checkbox custom-control-input" type = "checkbox" />
            <label id = "{{ $cadastro }}-supervisor-lbl" for = "{{ $cadastro }}-supervisor-chk" class = "custom-control-label lbl-permissao"></label>
        </div>
    </div>
</div>