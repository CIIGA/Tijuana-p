<?php

namespace App\Http\Controllers;

use App\Models\cobranzaExternaHistoricos;
use App\Models\determinacionesA;
use App\Models\ejecutores_ra;
use App\Models\implementta;
use App\Models\requerimientosA;
use App\Models\tabla_da;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Luecano\NumeroALetras\NumeroALetras;
use NumberFormatter;
use Illuminate\Support\Facades\View;
use App\Models\ejecutores_da;

class RequerimientoController extends Controller
{
    public function index($cuenta)
    {
        //validamos si la cuenta existe en la tabla cobranza
        $existe = DB::select('select count(NoCta)as c from cobranzaExternaHistoricosWS3 where NoCta = ?', [$cuenta]);
        //si no existe mandamos un error
        if (($existe[0]->c) == 0) {
            return  redirect()->action(
                [IndexController::class, 'index']
            )->with('error', 'error');
        }
        //si existe
        else {
           
            //consultamos si ya tiene una determinacion
            $determinacion = DB::select('select count(cuenta) as c from determinacionesA where cuenta=?', [$cuenta]);
            //si no tiene mandamos un alert que primero nececita una determinacion
            if (($determinacion[0]->c) == 0) {
                return  redirect()->action(
                    [IndexController::class, 'index']
                )->with('accessDeniedRequerimiento', 'error');
            }
            //en caso que ya tiene una determinacion
            else {
                //Lista de usuarios activos
                $usuarios = ejecutores_da::distinct()->pluck('ejecutor');


                //consultamos los datos para la tabla
                // $sql = cobranzaExternaHistoricos::select(['NoCta', 'anio', 'mes'])->where('NoCta', $cuenta)->orderBy('anio', 'ASC')->get();
                //consultamos el id de la determinacion
                $id = DB::select('select id from determinacionesA where cuenta = ?', [$cuenta]);
                $count_r = DB::select('select count(id) as c from requerimientosA where id_d = ?', [$id[0]->id]);
                $id_r=0;
                if ($count_r[0]->c != 0) {
                    $sql_id_r = DB::select('select id from requerimientosA where id_d = ?', [$id[0]->id]);
                    $id_r= $sql_id_r[0]->id;
                }
                
                //consultamos los datos de la tabla de determinacion
                $date = determinacionesA::select(
                    'folio',
                    'propietario as Propietario',
                    'clavec as Clave',
                    'seriem as SerieMedidor',
                    'domicilio as Domicilio',
                    'cuenta as Cuenta',
                    'multas',
                    'gastos_ejecución',
                    DB::raw('(convenio_agua+recargos_convenio_agua+convenio_obra+recargos_convenio_obra) as con_vencido'),
                    'otros_servicios',
                    'saldo_total as total',
                    'fechad',
                    'id',
                    'periodo',
                    'tipo_s as TipoServicio',
                    'recargos_consumo',
                    'rezago',
                    'atraso',
                    'corriente'
                )
                    ->where('id', $id[0]->id)
                    ->get();
                $recargos = $date[0]->recargos_consumo;
                $rezago = $date[0]->rezago;
                $atraso = $date[0]->atraso;
                $corriente = $date[0]->corriente;

                $multas = $date[0]->multas;
                $gastos_ejecucion = $date[0]->gastos_ejecución;
                $conv_vencido = $date[0]->con_vencido;
                $otros_gastos = $date[0]->otros_servicios;// se cambio el item -> otros_gastos a otros servicios, lo demas sigue igual
                
                //obtenemos los datos de la tabla adeudo
                $t_adeudo_t = tabla_da::select(['totalPeriodo', 'RecargosAcumulados', DB::raw("(RecargosAcumulados+totalPeriodo) as total")])
                    ->where('cuenta', $cuenta)->orderBy('meses', 'ASC')->first();
                // $tipos = implementta::select('TipoServicio')
                //     ->where('implementta.Cuenta', $cuenta)
                //     ->get();
             
                
                //validamos el tipo de servicio
                if ($date[0]->TipoServicio == "R" || $date[0]->TipoServicio == "RESIDENCIAL"|| $date[0]->TipoServicio == "DOMESTICO") {
                    $ts = 'DOMESTICO';
                } else {
                    $ts = 'NO DOMESTICO';
                }
                //establecemos los ceros en los folios
                $folio = $date[0]->folio;
                $longitud = strlen($folio);
                if ($longitud <= 5) {
                    while ($longitud < 5) {
                        $folio = "0" . $folio;
                        $longitud = strlen($folio);
                    }
                }
                $total_ar = ($rezago + $atraso + $corriente) + $recargos + number_format($multas, 2) + $gastos_ejecucion + $conv_vencido + $otros_gastos;
                //extraemos el entero
                $entero = floor($total_ar);
                //extraemos el decimal
                $decimal = round($total_ar - $entero, 2) * 100;
                //convertiremos el total del adeudo requerido en letras
                $formatter = new NumeroALetras();
                //convertimos en texto el entero
                $texto_entero = $formatter->toMoney($entero);
                //concatenamos para obtener todo el texto
                $tar = ' (' . $texto_entero . ' ' . $decimal . '/100 Moneda Nacional)';
                return view('components.formRequerimiento', [
                    'usuarios' => $usuarios,
                    'id_r' => $id_r,
                    'date' => $date,
                    'folio' => $folio,
                    'ts' => $ts,
                    'multas' => $multas,
                    'gastos_ejecucion' => number_format($gastos_ejecucion, 2),
                    'conv_vencido' => number_format($conv_vencido, 2),
                    'otros_gastos' => number_format($otros_gastos, 2),
                    't_adeudo_t' => $t_adeudo_t, 
                    'total_ar' => number_format($total_ar, 2),
                    'tar' => $tar,
                    'recargos_consumo' =>  number_format($recargos, 2),
                    'rezago' =>  number_format($rezago, 2),
                    'atraso' =>  number_format($atraso, 2),
                    'corriente' =>  number_format($corriente, 2),
                ]);
            }
        }
    }
    public function store(Request $request)
    {
        $request->validate([
            'emision' =>  ['required'],
            'tservicio' =>  ['required'],
            'notificacion' =>  ['required'],
            'sobrerecaudador' =>  ['required'],
            'nombramiento' =>  ['required'],
        ]);
        //validar si esta cuenta ya tiene un requerimiento
        $validar = requerimientosA::join('determinacionesA as d', 'requerimientosA.id_d', '=', 'd.id')
            ->where('d.id', $request->id_d)
            ->count();

            $cuenta_d = DB::select('select cuenta from determinacionesA where id = ?', [$request->id_d]);
            $cuenta = $cuenta_d[0]->cuenta;
        //si existe

        if ($validar != 0) {
            //consultar el id del requerimiento
            $id = requerimientosA::select('id')->where('id_d', $request->id_d)->first();
            //declaramos que se va a modificar el registro de requerimiento
            $r = requerimientosA::findOrFail($id->id);
        }
        //no existe
        else {
            //declaramos que se creara un nuevo registro en requerimientosA
            $r = new requerimientosA();
        }
        //guardamos los datos en requerimientosA
        $r->id_d = $request->id_d;
        $r->fechar = $request->emision;
        $r->fechand = $request->notificacion;
        $r->sobrerecaudador = $request->sobrerecaudador;
        $r->tipo_s = $request->tservicio;
        $r->nombramiento = $request->nombramiento;
        $r->save();
        //validamos si se guardaron los datos
        if ($r->save()) {
           
            //consultamos su id
            $requirimiento = requerimientosA::select('id')->where('id_d', $request->id_d)->first();
            $id = $requirimiento->id;
            
            ejecutores_ra::where('id_r', $cuenta)->update(['id_r' => $id]);
            //si se guardaron los datos retornamos el pdf
            
                return '<script type="text/javascript">window.open("PDFRequerimiento/' . $id . '")</script>' .
                    redirect()->action(
                        [IndexController::class, 'index']
                    );
            
        } else {
            return back()->with('errorPeticion', 'Error al generar');
        }
    }
    public function pdf($id)
    {
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '-1');
        //Consulta de la determinacion y del requerimiento
        $datos = determinacionesA::join('requerimientosA as r', 'r.id_d', '=', 'determinacionesA.id')
            ->select([
                'r.id', 'folio', DB::raw("format(fechad,'dd'' de ''MMMM'' de ''yyyy','es-es') as fechad"),
                'cuenta', 'propietario', 'domicilio', 'clavec', 'r.tipo_s as tipo_s', 'seriem', 'razons', 'periodo', 'fechand',
                DB::raw("format(fechar,'dd'' de ''MMMM'' de ''yyyy','es-es') as fechar"),
                DB::raw("format(fechar,'dd'' días del mes de ''MMMM'' del año ''yyyy','es-es') as fechar2"),
                DB::raw("format(fechand,'dd'' de ''MMMM','es-es') as fd",'id_d'),
                'multas',
                'gastos_ejecución',
                DB::raw('(convenio_agua+recargos_convenio_agua+convenio_obra+recargos_convenio_obra) as con_vencido'),
                'recargos_consumo',
                'rezago',
                'atraso',
                'corriente',
                'otros_servicios',
                'saldo_total as total',
                'sobrerecaudador',
                'r.ejecutores',
                'r.nombramiento'
            ])
            ->where('r.id', $id)
            ->get();
           
            $ejecutores = requerimientosA::join('ejecutores_ra as e', 'e.id_r', '=', 'requerimientosA.id')->select('ejecutor')->where('id', $id)->get();
        // Conteo del total de ejecutores
        $count_ejecutor = requerimientosA::join('ejecutores_ra as e', 'e.id_r', '=', 'requerimientosA.id')->select('ejecutor')->where('id', $id)->count();

        // Formateando ejecutores
        $ejecutoresformat = '';

        if ($count_ejecutor > 0) {
            // Recorriendo los ejecutores
            for ($i = 0; $i < $count_ejecutor; $i++) {
                if ($ejecutores[$i]->ejecutor != 'none') {
                    if ($i == ($count_ejecutor - 1)) {
                        if ($count_ejecutor > 1) {
                            $ejecutoresformat .= ' y ' . $ejecutores[$i]->ejecutor;
                        } else {
                            $ejecutoresformat .= $ejecutores[$i]->ejecutor;
                        }
                    } else if ($i == ($count_ejecutor - 2)) {
                        $ejecutoresformat .= $ejecutores[$i]->ejecutor;
                    } else {
                        $ejecutoresformat .= $ejecutores[$i]->ejecutor . ', ';
                    }
                } else {
                    $ejecutoresformat = 'none';
                    break; // Salir del bucle si se encuentra "none"
                }
            }

            // Validar si es un solo ejecutor o varios
            if ($count_ejecutor > 1) {
                $ejecutoresformat = 'C.C. ' . $ejecutoresformat;
            } else {
                $ejecutoresformat = 'C. ' . $ejecutoresformat;
            }
        } else {
            $ejecutoresformat = 'none';
        }
        //obtenemos los datos de la tabla adeudo-
       
        //convertivos la fecha en año para convertirlo en texto y concatenarlo con la fecha fd
        $formato = new NumeroALetras();
        //Convirtiendo la fecha en fecha corta
        $f = strtotime($datos[0]->fechand);
        //Extrayendo el año
        $anio = date("Y", $f);
        //Convirtiendo el año en letra ejemplo 2020 en dos mil veite
        $conversion = $formato->toString($anio);
        //Concatenando la fecha
        $fechaNotiDeter = $datos[0]->fd . ' de ' . mb_strtolower(substr($conversion, 0, -1), "UTF-8");
        //establecemos los ceros en los folios
        $folio = $datos[0]->folio;
        $longitud = strlen($folio);
        if ($longitud <= 5) {
            while ($longitud < 5) {
                $folio = "0" . $folio;
                $longitud = strlen($folio);
            }
        }
        //convertiremos el total del adeudo requerido en letras
        $formatter = new NumeroALetras();
        //obtenemos el total del adeuto requerido
        $total_ar = $datos[0]->total;
        //extraemos el entero
        $entero = floor($total_ar);
        //extraemos el decimal
        $decimal = round($total_ar - $entero, 2) * 100;
        //convertimos en texto el entero
        $texto_entero = $formatter->toMoney($entero);
        //concatenamos para obtener todo el texto
        $tar = ' (' . $texto_entero . ' ' . $decimal . '/100 Moneda Nacional)';
        
        //declaramos la variable pdf y mandamos los parametros
        $pdf = Pdf::loadView('pdf.requerimiento', ['ejecutores' => $ejecutoresformat,'items' => $datos, 'fechaNotiDeter' => $fechaNotiDeter,'fechar'=>$datos[0]->fechar, 'fechar2'=>$datos[0]->fechar2,'folio' => $folio, 'tar' => $tar, 'total'=>$total_ar]);
        return $pdf->stream();
    }

    public function tabla_ejecutor($id,$cuenta)
    {
        if ($id == 0) {
            $datos = DB::select('select * from ejecutores_ra where id_r=:id order by ejecutor', ['id' => $cuenta]);
        } else {
            $datos = DB::select('select * from ejecutores_ra where id_r=:id order by ejecutor', ['id' => $id]);
        }
        

        $tabla = View::make('tabla_ejecutor_ra', compact('datos'))->render();
        //retornamos la respuesta json
        return response()->json([
            'tabla' => $tabla
        ]);
    }
    public function delete_ejecutor(Request $request)
{
    $ejecutor = $request->input('ejecutor');
    $id_r = $request->input('id'); // Asumí que este es el nombre del parámetro en la solicitud

    // Elimina el registro que coincida con ejecutor y id_d
    ejecutores_ra::where('ejecutor', $ejecutor)->where('id_r', $id_r)->delete();

    $datos = DB::select('select * from ejecutores_ra where id_r=:id order by ejecutor', ['id' => $id_r]);

        $tabla = View::make('tabla_ejecutor_ra', compact('datos'))->render();
        //retornamos la respuesta json
        return response()->json([
            'tabla' => $tabla
        ]);
}
    public function guardar_ejecutor(Request $request)
    {
        $ejecutor = $request->input('ejecutor');
        $id = $request->input('id');
        $cuenta = $request->input('cuenta');
        $e = new ejecutores_ra();
        $e->ejecutor = $ejecutor;

        
        
        if ($id == 0) {
            $e->id_r = $cuenta;
        } else {
            $e->id_r = $id;
        }
        $e->save();
        return $this->tabla_ejecutor($id,$cuenta);
    }
}
