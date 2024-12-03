
<!-- Modal -->
<div class = "modal fade" id = "empresasModal" aria-labelledby = "empresasModalLabel" aria-hidden = "true">
    <div class = "modal-dialog modal-dialog-centered" role = "document">
        <div class = "modal-content">
            <div class = "modal-header">
                <h6 class = "modal-title header-color" id = "empresasModalLabel"></h6>
                <button type = "button" class = "close" data-dismiss = "modal" aria-label = "Close">
                    <span aria-hidden = "true">&times;</span>
                </button>
            </div>
            <form action = "{{ config('app.root_url') }}/empresas/salvar" method = "POST">
                <div class = "modal-body">
                    <div class = "container">
                        @csrf
                        <input id = "id" name = "id" type = "hidden" />
                        <input id = "id_matriz" name = "id_matriz" type = "hidden" />
                        <div class = "row">
                            <div class = "col-12">
                                <label for = "cnpj" class = "custom-label-form">CNPJ: *</label>
                                <input id = "cnpj" name = "cnpj" class = "form-control" autocomplete = "off" type = "text" onkeyup = "formatar_cnpj(this)" />
                            </div>
                        </div>
                        <div class = "row">
                            <div class = "col-12">
                                <label for = "razao_social" class = "custom-label-form">Razão social: *</label>
                                <input id = "razao_social" name = "razao_social" class = "form-control" autocomplete = "off" type = "text" onkeyup = "contar_char(this, 128)" />
                                <span class = "custom-label-form tam-max"></span>
                            </div>
                        </div>
                        <div class = "row">
                            <div class = "col-12">
                                <label for = "nome_fantasia" class = "custom-label-form">Nome fantasia: *</label>
                                <input id = "nome_fantasia" name = "nome_fantasia" class = "form-control" autocomplete = "off" type = "text" onkeyup = "contar_char(this, 64)" />
                                <span class = "custom-label-form tam-max"></span>
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