
<!-- Modal -->
<div class = "modal fade" id = "pessoasModal" aria-labelledby = "pessoasModalLabel" aria-hidden = "true">
    <div class = "modal-dialog modal-xl" role = "document">
        <div class = "modal-content">
            <div class = "modal-header">
                <h6 class = "modal-title header-color" id = "pessoasModalLabel"></h6>
                <button type = "button" class = "close" data-dismiss = "modal" aria-label = "Close">
                    <span aria-hidden = "true">&times;</span>
                </button>
            </div>
            <form action = "{{ $root_url }}/colaboradores/salvar" method = "POST" enctype = "multipart/form-data">
                <div class = "modal-body">
                    <div class = "container">
                        @csrf
                        <input id = "pessoa-id" name = "id" type = "hidden" />
                        <div class = "row py-4">
                            <div class = "user-pic" style = "scale:2">
                                <span class = "m-auto">
                                    @foreach(explode(" ", Auth::user()->name, 2) as $nome)
                                        {{ substr($nome, 0, 1) }}
                                    @endforeach
                                </span>
                            </div>
                        </div>
                        <div class = "row">
                            <div class = "col-11 pr-0">
                                <input type = "hidden" name = "id_empresa" id = "pessoa-id_empresa" />
                                <label for = "pessoa-empresa-select" class = "custom-label-form">Empresa: *</label>
                                <select id = "pessoa-empresa-select" class = "form-control" onchange = "pessoa.mudou_empresa($(this).val())"></select>
                            </div>
                            <div class = "col-1 pt-4 d-flex align-items-center">
                                <i
                                    class = "fa-sharp fa-regular fa-arrow-up-right-from-square atalho"
                                    data-atalho = "empresas"
                                    data-campo_id = "pessoa-empresa-select"
                                    data-campo_descr = ""
                                ></i>
                            </div>
                        </div>
                        <div class = "row">
                            <div class = "col-4">
                                <label for = "nome" class = "custom-label-form">Nome: *</label>
                                <input id = "nome" name = "nome" class = "form-control" autocomplete = "off" type = "text" onkeyup = "contar_char(this, 64)" />
                                <span class = "custom-label-form tam-max"></span>
                            </div>
                            <div class = "col-4">
                                <label for = "cpf" class = "custom-label-form">CPF: *</label>
                                <input id = "cpf" name = "cpf" class = "form-control" autocomplete = "off" type = "text" onkeyup = "formatar_cpf($(this))" />
                            </div>
                            <div class = "col-4">
                                <button type = "button" class = "btn btn-target btn-target-black w-100 mt-4" onclick = "$(this).next().trigger('click')">Adicionar imagem</button>
                                <input type = "file" name = "foto" class = "d-none" />
                            </div>
                        </div>
                        <div class = "row row-setor">
                            <div class = "col-4">
                                <label for = "funcao" class = "custom-label-form">Função: *</label>
                                <input id = "funcao" name = "funcao" class = "form-control" autocomplete = "off" type = "text" onkeyup = "contar_char(this, 64)" />
                                <span class = "custom-label-form tam-max"></span>
                            </div>
                            <div class = "col-4">
                                <label for = "admissao" class = "custom-label-form">Admissão: *</label>
                                <input id = "admissao" name = "admissao" class = "form-control data" autocomplete = "off" type = "text" />
                            </div>
                            <div class = "col-3 pr-0">
                                <input type = "hidden" name = "id_setor" id = "id_setor" />
                                <label for = "pessoa-setor-select" class = "custom-label-form">Centro de custo: *</label>
                                <select id = "pessoa-setor-select" class = "form-control" onchange = "pessoa.mudou_setor($(this).val())"></select>
                            </div>
                            <div class = "col-1 d-flex align-items-center">
                                <i
                                    class = "fa-sharp fa-regular fa-arrow-up-right-from-square atalho"
                                    data-atalho = "setores"
                                    data-campo_id = "pessoa-setor-select"
                                    data-campo_descr = ""
                                ></i>
                            </div>
                        </div>
                        <div class = "row">
                            <div class = "col-4">
                                <label for = "email" id = "email-lbl" class = "custom-label-form">E-mail:</label>
                                <input id = "email" name = "email" class = "form-control" autocomplete = "off" type = "text"/>
                            </div>
                            <div class = "col-4">
                                <label for = "telefone" class = "custom-label-form">Telefone: *</label>
                                <input id = "telefone" name = "telefone" class = "form-control" autocomplete = "off" type = "text" onkeyup = "this.value=phoneMask(this.value)" />
                            </div>
                            <div class = "col-4">
                                <label for = "matricula" class = "custom-label-form">Matrícula:</label>
                                <input id = "matricula" name = "matricula" class = "form-control" autocomplete = "off" type = "text" onkeyup = "contar_char(this, 32)" />
                                <span class = "custom-label-form tam-max"></span>
                            </div>
                        </div>
                        <div class = "row row-senha">
                            <div class = "col-5">
                                <label for = "senha" id = "senha-lbl" class = "custom-label-form">Senha numérica: *</label>
                                <input id = "senha" name = "senha" class = "form-control" autocomplete = "off" type = "password" onkeyup = "numerico(this)" title = "Senha para retirar produtos" />
                            </div>
                            <div class = "col-1 pt-4 d-flex align-items-center">
                                <i id = "mostrar_senha" class = "fal fa-eye-slash" onclick = "pessoa.mostrar_senha()"></i>
                            </div>
                            <div class = "col-6">
                                <label for = "password" id = "password-lbl" class = "custom-label-form">Senha alfanumérica: *</label>
                                <input id = "password" name = "password" class = "form-control" autocomplete = "off" type = "password" title = "Senha para acessar essa plataforma" />
                            </div>
                        </div>
                        @include("components.spv_vis", ["cadastro" => "pessoa", "tipo" => "visitante"])
                        @include("components.spv_vis", ["cadastro" => "pessoa", "tipo" => "supervisor"])
                    </div>
                </div>
                <div class = "d-flex">
                    <button type = "button" class = "btn btn-target mx-auto my-4 mb-4 px-5" onclick = "pessoa.validar()">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>