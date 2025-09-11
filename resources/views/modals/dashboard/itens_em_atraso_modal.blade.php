
<!-- Modal -->
<div class = "modal fade" id = "itensEmAtrasoModal" aria-labelledby = "itensEmAtrasoModalLabel" aria-hidden = "true">
    <div class = "modal-dialog modal-xl modal-xl-kx modal-dialog-scrollable" role = "document">
        <div class = "modal-content">
            <div class = "modal-header">
                <h4 class = "modal-title header-color" id = "itensEmAtrasoModalLabel">Produtos em atraso</h4>
                <button type = "button" class = "close" data-dismiss = "modal" aria-label = "Close">
                    <span aria-hidden = "true">&times;</span>
                </button>
            </div>
            <div class = "modal-body">
                <div class = "container">
                    <div class = "w-100">
                        <table id = "table-itens-em-atraso" class = "table table-striped mb-4">
                            <thead>
                                <tr>
                                    <th>Produto</th>
                                    <th class = "text-right">Quantidade</th>
                                    <th class = "text-right">Validade em dias</th>
                                </tr>
                            </thead>
                            <tbody id = "table-itens-em-atraso-dados"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>