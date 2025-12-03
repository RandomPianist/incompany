$(document).ready(function() {
    $(".traduzir").each(function() {
        $(this).html(
            $(this).html()
                .replace("Monday", "Segunda-feira")
                .replace("Tuesday", "Terça-feira")
                .replace("Wednesday", "Quarta-feira")
                .replace("Thursday", "Quinta-feira")
                .replace("Friday", "Sexta-feira")
                .replace("Saturday", "Sábado")
                .replace("Sunday", "Domingo")
                .replace("January", "janeiro")
                .replace("February", "fevereiro")
                .replace("March", "março")
                .replace("April", "abril")
                .replace("May", "maio")
                .replace("June", "junho")
                .replace("July", "julho")
                .replace("August", "agosto")
                .replace("September", "setembro")
                .replace("October", "outubro")
                .replace("November", "novembro")
                .replace("December", "dezembro")
        );      
        if (location.href.indexOf("solicitacoes") > -1) {
            $("#btn-print").addClass("d-none");
            $("#btn-export").addClass("d-none");
            carregar();
        } else $("#menu1").addClass("d-none");
        if (location.href.indexOf("ranking") > -1) {
            $("#menu").addClass("d-none");
            $("#btn-print").removeClass("d-none");
        }
    });

    $(".dinheiro").each(function() {
        let resultado = dinheiro($(this).html());
        if ($(this).hasClass("analitico")) resultado = "Preço: " + resultado;
        $(this).html(resultado);
    });
});

function exportar() {
    var csv = [];
    var BOM = "\uFEFF";

    var container = document.querySelector(".report");
    if (!container) return alert("Erro: Container .report não encontrado.");

    var url = window.location.href;
    var telaPessoas = (url.indexOf("pessoas") > -1);
    var tipoSintetico = (url.indexOf("tipo=S") > -1 );
    var extratoItens = (url.indexOf("extrato?resumo=N") > -1);
    
    var nomeCol1 = "Grupo";
    var nomeCol2 = "Centro de Custo";
    var produtoAtual = "";

    if (url.indexOf("extrato") > -1 || 
        url.indexOf("solicitacoes") > -1 || 
        url.indexOf("sugestao") > -1 || 
        url.indexOf("empresas-por-maquina") > -1) {
        nomeCol1 = "Máquina";
    } else if (url.indexOf("produto") > -1) {
        nomeCol1 = "Categoria";
    } else if (url.indexOf("retiradas?rel_grupo=pessoa") > -1) {
        nomeCol1 = "Colaborador";
    } else if (url.indexOf("retiradas?rel_grupo=setor") > -1) {
        nomeCol1 = "Centro de Custo";
    } else if (telaPessoas || url.indexOf("maquinas-por-empresa") > -1) {
        nomeCol1 = "Empresa";
    }

    var seletor = "h5, table.report-body";
    if (telaPessoas) seletor += ", h6.fw-600";
    if (extratoItens) seletor += ", table.w-100";
    var elementos = container.querySelectorAll(seletor);

    var valorNivel1 = "";
    var valorNivel2 = "";
    var cabecalhoGerado = false;

    var tituloRelatorio = "relatorio";
    var elTitulo = document.querySelector(".report-header h6.fw-600");
    if (elTitulo) {
        tituloRelatorio = elTitulo.innerText.trim().replace(/\s+/g, "_").normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase();
    }

    elementos.forEach(function(el) {
        if (el.tagName === "H5") {
            valorNivel1 = el.innerText.trim();
            valorNivel2 = "";
            return;
        }
        if (telaPessoas && el.tagName === "H6") {
            valorNivel2 = el.innerText.trim();
            return;
        }
        if (extratoItens && el.tagName === "TABLE" && el.classList.contains("w-100")) {
            var produto = el.querySelectorAll("h6");
            if (produto.length >= 1) produtoAtual = produto[0].innerText.trim();
            return;
        }

        if (el.tagName === "TABLE") {
            var linhas = el.querySelectorAll("tr");

            linhas.forEach(function(tr) {
                var cols = tr.querySelectorAll("td, th");
                var linhaCSV = [];

                var header = tr.closest('thead') || tr.querySelector('th') || (!cabecalhoGerado);

                if (!cabecalhoGerado && header) {

                    if (!tipoSintetico) linhaCSV.push('"' + nomeCol1 + '"');

                    if (telaPessoas) linhaCSV.push('"' + nomeCol2 + '"');

                    if (extratoItens) linhaCSV.push('"Produto"');

                    cols.forEach(function(col) {
                        linhaCSV.push('"' + col.innerText.trim().replace(/"/g, '""') + '"');
                    });

                    csv.push(linhaCSV.join(";"));
                    cabecalhoGerado = true;
                    return;
                }

                if (!header && tr.querySelector("td")) {
                    if (!tipoSintetico) linhaCSV.push('"' + valorNivel1.replace(/"/g, '""') + '"');

                    if (telaPessoas) linhaCSV.push('"' + valorNivel2.replace(/"/g, '""') + '"');

                    if (extratoItens) linhaCSV.push('"' + produtoAtual.replace(/"/g, '""') + '"');

                    cols.forEach(col => {
                        var texto = col.innerText
                            .replace(/(\r\n|\n|\r)/gm, "")
                            .replace(/\s\s+/g, " ")
                            .trim()
                            .replace(/"/g, '""');
                        linhaCSV.push('"' + texto + '"');
                    });

                    csv.push(linhaCSV.join(";"));
                }
            });
        }
    });

    var csv_string = BOM + csv.join("\n");
    var filename = tituloRelatorio + ".csv";
    
    var link = document.createElement("a");
    link.style.display = "none";
    link.setAttribute("href", "data:text/csv;charset=utf-8," + encodeURIComponent(csv_string));
    link.setAttribute("download", filename);
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}