
<!-- Modal -->
<div class = "modal fade" id = "setoresModal" aria-labelledby = "setoresModalLabel" aria-hidden = "true">
    <div class = "modal-dialog" role = "document">
        <div class = "modal-content">
            <div class = "modal-header">
                <h6 class = "modal-title header-color" id = "setoresModalLabel"></h6>
                <button type = "button" class = "close" data-dismiss = "modal" aria-label = "Close">
                    <span aria-hidden = "true">&times;</span>
                </button>
            </div>
            <form action = "{{ config('app.root_url') }}/setores/salvar" method = "POST">
                <div class = "modal-body">
                    <div class = "container">
                        @csrf
                        <input id = "id" name = "id" type = "hidden" />
                        <div class = "row">
                            <div class = "col-12">
                                <label for = "descr" class = "custom-label-form">Descrição: *</label>
                                <input id = "descr" name = "descr" class = "form-control" autocomplete = "off" type = "text" onkeyup = "contar_char(this, 32)" />
                                <span class = "custom-label-form tam-max"></span>
                            </div>
                        </div>
                        <div class = "row mb-3 pt-3">
                            <div class = "col-12">
                                <div class = "custom-control custom-switch">
                                    <input id = "cria_usuario" name = "cria_usuario" type = "hidden" />
                                    <input id = "cria_usuario-chk" class = "checkbox custom-control-input" type = "checkbox" onchange = "muda_cria_usuario(this)" />
                                    <label for = "cria_usuario-chk" class = "custom-control-label">Cria usuários<label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class = "d-flex">
                    <button type = "button" class = "btn btn-target mx-auto mb-4 px-5" onclick = "validar()">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>