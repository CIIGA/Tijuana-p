<!-- Tu vista de Laravel -->
@foreach ($datos as $item)
    <tr>
        <td class="table-light" style="text-align:center;">
            {{ $item->ejecutor }}
        </td>
        <td class="table-light" style="text-align:center;">
            <button type="button" class="btnDeleteEjecutor btn btn-light btn-sm" title="Eliminar"
                data-id="{{ $item->id_r }}" data-ejecutor="{{ $item->ejecutor }}">
                <img src="https://img.icons8.com/color/24/delete-forever.png" />
            </button>
        </td>
    </tr>
@endforeach

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

<script>
    $(document).ready(function() {
        // Eliminar cualquier evento duplicado antes de agregar uno nuevo
        $(document).off('click', '.btnDeleteEjecutor').on('click', '.btnDeleteEjecutor', function() {
            
            var id = $(this).data('id');
            var ejecutor = $(this).data('ejecutor');

            // Confirmar con el usuario antes de eliminar
            
                // Enviar la solicitud AJAX para eliminar el registro
                $.ajax({
                    url: 'https://gallant-driscoll.198-71-62-113.plesk.page/implementta/modulos/Tijuana-p/public/delete_ejecutor_r',
                    method: 'GET',
                    data: {
                        ejecutor: ejecutor,
                        id: id // Asegúrate de que el nombre del parámetro coincida con el del controlador
                    },
                    success: function(response) {
                        // Manejar la respuesta del servidor (opcional)
                        if (response.tabla) {
                            var tabla = $("#tabla_ejecutor tbody"); // Muestra la tabla reporte con el registro ya eliminado
                            tabla.html(response.tabla);
                        }
                    },
                    error: function(xhr, status, error) {
                        // Manejar errores (opcional)
                        toastr.error('Hubo un error al eliminar el ejecutor.', 'Error');
                    }
                });
            
        });
    });
</script>
