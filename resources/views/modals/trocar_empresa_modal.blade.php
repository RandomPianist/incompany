
<!-- Modal -->
<div class = "modal fade" id = "trocarEmpresaModal" aria-labelledby = "trocarEmpresaModalLabel" aria-hidden = "true">
    <div class = "modal-dialog modal-dialog-centered" role = "document">
        <div class = "modal-content">
            <div class = "modal-header">
                <h6 class = "modal-title header-color" id = "trocarEmpresaModalLabel">Trocar empresa</h6>
                <button type = "button" class = "close" data-dismiss = "modal" aria-label = "Close">
                    <span aria-hidden = "true">&times;</span>
                </button>
            </div>
            <div class = "modal-body">
                <div class = "container">
                    <div class = "row">
                        <div class = "col-12">
                            <select name = "empresa" id = "empresa-select" class = "form-control"></select>
                        </div>
                    </div>
                </div>
            </div>
            <div class = "d-flex">
                <button type = "button" class = "btn btn-target mx-auto my-4 px-5" onclick = "trocarEmpresa()">Salvar</button>
            </div>
        </div>
    </div>
</div>