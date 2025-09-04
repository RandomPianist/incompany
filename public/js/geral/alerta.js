async function s_alert(obj) {
    if (typeof obj == "string") {
        const aux = obj;
        obj = {
            icon : "warning",
            html : aux
        }
    }
    if (obj.invert === undefined) obj.invert = false;
    if (obj.yn === undefined && obj.invert) obj.yn = true;
    let el = document.getElementsByClassName("custom-scrollbar")[0];
    if (el !== undefined) var scroll = el.scrollTop;
    let json = {
        showDenyButton : false
    };
    if (obj.icon !== undefined) {
        json.icon = obj.icon;
        switch(obj.title) {
            case "success":
                json.title = "Sucesso";
                break;
            case "warning":
                json.title = "Atenção";
                break;
            case "error":
                json.title = "Erro";
                break;
        }
    } else if (obj.title !== undefined) json.title = obj.title;
    if (obj.html !== undefined) json.html = obj.html;
    if (obj.yn !== undefined) {
        if (obj.yn) {
            json.showDenyButton = true;
            json.confirmButtonText = obj.invert ? "NÃO" : "SIM";
        }
    }
    json.confirmButtonColor = "rgb(31, 41, 55)";
    const resultado = await Swal.fire(json);
    if (el !== undefined) {
        setTimeout(function() {
            el.scrollTo(0, scroll);
        }, 400);
    }
    return json.showDenyButton ? obj.invert ? resultado.isDenied : resultado.isConfirmed : true;
}