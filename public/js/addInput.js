


// Espera a que la vista se cargue completamente
document.addEventListener("DOMContentLoaded", function () {
    $(document).ready(function () {
        //cargamos la tabla reporte con los datos que ya tiene
        var id = document.getElementById("id").value;
        var cuenta = document.getElementById("cuenta").value;
        if (id == 0) {
            
        } else {
            $.ajax({
                url:"https://gallant-driscoll.198-71-62-113.plesk.page/implementta/modulos/Tijuana-p/public/tabla_ejecutor/" + id + "/" + cuenta, //nos redirijimos a esta ruta
                type: "GET", //por el metodo get
                dataType: "json", //se recibira un dato json
                success: function (response) {
                    var tabla = $("#tabla_ejecutor tbody"); //mostramos la tabla reporte con el registro ya eliminado
                    tabla.html(response.tabla);
                    
                
                    
                },
                error: function (xhr, status, error) {
                    $("#loading-container").hide();
                    // console.log(error);
                    Swal.fire({
                        title: "Error",
                        text: "Error al ejecutar, intentelo de nuevo y si el problema persiste comuniquese con soporte",
                        icon: "error",
                        showConfirmButton: true,
                    });
                },
            });
        }
        
    });
});


