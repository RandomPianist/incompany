
<!-- Modal -->
<div class = "modal fade" id = "retiradasListaModal" aria-labelledby = "retiradasListaModalLabel" aria-hidden = "true">
    <div class = "modal-dialog modal-xl" role = "document">
        <div class = "modal-content">
            <div class = "modal-header">
                <h4 class = "modal-title header-color" id = "retiradasListaModalLabel">Produtos retirados</h4>
                <button type = "button" class = "close" data-dismiss = "modal" aria-label = "Close">
                    <span aria-hidden = "true">&times;</span>
                </button>
            </div>
            <div class = "modal-body">
                <div class = "container">
                    <div class = "w-100">
                        <table id = "table-retirados" class = "table table-striped mb-4">
                            <thead>
                                <tr>
                                    <th>Produto</th>
                                    <th class = "text-right">Quantidade</th>
                                </tr>
                            </thead>
                            <tbody id = "table-retirados-dados"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>