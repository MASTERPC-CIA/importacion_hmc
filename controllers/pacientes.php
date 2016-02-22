<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of paciente
 *
 * @author satellite
 */
class Pacientes extends MX_Controller {

    private $nacionalidades_list;
    private $provincias_list;
    private $cantones_list;
    private $parroquias_list;
    private $sexo_list;
    private $estado_civil_list;
    private $etnia_list;
    private $grado_list;
    private $unidades_list;
    private $tarifa_cliente_tipo_list;
    private $archivo_name;
    private $row_file;
    private $es_pasaporte;
    private $PersonaComercio_cedulaRuc;
    // Creo las variables para ocupar la funcion de nuhc
    private $nombre;
    private $apellido;
    private $provincia_id;
    private $nacionalidad_id;
    private $fecha_nac;
    private $num_archivo;

    function __construct() {
        parent::__construct();
        $this->load->library('docident'); // validacion de cedulas ruc etc
        //NOTA  Nacionalidades tiene un formato de registro diferente en el excel
        $this->nacionalidades_list = $this->generic_model->get('nacionalidad', array('id >' => '0', 'id <' => '5'), 'id, SUBSTRING(nombre,1,3)nombre');
        $this->provincias_list = $this->generic_model->get('bill_provincia', null, 'idProvincia id, descripProv nombre, codigoProv codigo');
        $this->cantones_list = $this->generic_model->get('bill_canton', array('idCanton >' => '0'), 'idCanton id, descripCtn nombre');
        $this->parroquias_list = $this->generic_model->get('bill_parroquia', array('idParroquia >' => '0'), 'idParroquia id, descripPq nombre');
        //NOTA se requiere nueva funcion para extraer id sexo
        $this->sexo_list = $this->generic_model->get('cliente_sexo', array('id >' => '0'), 'id, SUBSTRING(nombre,1,1)nombre');
        //NOTA se requiere nueva funcion para extraer id estado civil
        $this->estado_civil_list = $this->generic_model->get('cliente_estado_civil', array('id >' => '0'), 'id, SUBSTRING(nombre,1,1)nombre');
        // Listadod e las Etnias
        $this->etnia_list = $this->generic_model->get('cliente_etnia', array('id >' => '0'), 'id, nombre');
        //NOTA se requiere nueva funcion para extraer grado_id para militar y para familiar
        $this->grado_list = $this->generic_model->get('cliente_grado', array('id >' => '0'), 'id, nombre');
        //NOTA se requiere nueva funcion para extraer id unidad para militar y para familiar
        $this->unidades_list = $this->generic_model->get('unidad_ffaa', array('id >' => '0'), 'id, uni_nombre_abr nombre');
        // NOTA saco los tiposde clientes para saber cuales son activos pasivos y civiles
//        $this->tarifa_cliente_tipo_list = $this->generic_model->get('billing_clientetipo', array('idclientetipo >' => '0'), 'idclientetipo id');
    }

    function index() {
        $res['title'] = 'Importar Pacientes';
        $res['view'] = $this->load->view('pacientes_view', '', TRUE);
        $res['slidebar'] = $this->load->view('slidebar_lte', '', TRUE);
        $this->load->view('common/templates/dashboard_lte', $res);
    }

    function importar() {
        $string = $this->input->post('string');
//        $date= date_format(date_create($string), 'Y-m-d');
//        echo $date;
//        die();
//        $this->get_nacionalidadId($string, $this->nacionalidades_list, 'Nacionalidad');
//        $this->get_coincidencias($string, $this->sexo_list, 'sexo');
//        $this->get_estadoCivilId($string, $this->estado_civil_list, 'Extado Civil');
//        $id_estado = $this->ver_estado_militar($string);
//        die();
//        echo $this->verificar_fecha($string);
        
//        $string = (int) $string;
//        $string = strrpos($string,'0');
        $cont = $this->contar_n_caracteres($string,0);
        
//            echo "Este cedula tiene ".$cont." ceros";
        if($cont == strlen($string)){
            echo "Este cedula tiene ".$cont." ceros";
        }
        $valido = $this->validar_caracteres_permitidos($string);
        if($valido){
            echo "<br>String valido => ".$valido;
        }else{
            echo "<br>String no valido => ".$valido;
            
        }
        die();
        echo $this->get_nuhc('Juan Ramon', 'Pérez Andrade', '-1', '1', '');
        die();
        $id_grado = $this->get_grado_id_unidad_id($string, $this->grado_list, "SUBTE", "CBOS"
                , null, null, null, 1, 0);
        $id_unidad = $this->get_grado_id_unidad_id($string, null, null, null
                , $this->unidades_list, "BI-8", "III-DE", 0, 0);
        echo "id_grado " . $id_grado;
        echo "<br>id_unidad " . $id_unidad;
    }

    function importar1() {
        $upload_path = 'pacientes';
//        $this->get_idbanco($nombre_banco);
        $this->loadfromfile($upload_path);
    }

    public function loadfromfile($upload_path) {
        set_time_limit(0);
        $this->load->library('excel');
        $this->load->library('docident');
        $this->load->library('common/cuentasxpagar');
        $this->load->helper('date_helper');

        $config['upload_path'] = './uploads/' . $upload_path;
        $config['allowed_types'] = 'xlsx';
        $config['max_size'] = '0';
        $config['max_width'] = '0';
        $config['max_height'] = '0';

        $this->load->library('upload', $config);

        if (!$this->upload->do_upload()) {
            $error = $this->upload->display_errors();
            echo tagcontent('strong', $error, array('class' => 'text-danger font20'));
            die();
        } else {
            $upl_data = $this->upload->data();
        }

        $upl_data = $this->upload->data();

        $this->get_pacientesdata_xls($upl_data, $upload_path);

        /*         * *Finalizamos la transaccion** */
        if ($this->db->trans_status() === FALSE) {
            echo warning_msg(' Ha ocurrido un problema, no se pudo completar la transaccion.');
            $this->db->trans_rollback();
        } else {
            echo success_msg(' EL PROCESO A TERMINADO CON EXITO');
            $this->db->trans_commit();
        }

        echo tagcontent('strong', 'Se ha terminado de cargar el listado de clientes', array('class' => 'text-success font20'));
    }

    function get_pacientesdata_xls($xls_data, $upload_path) {
        if (file_exists('./uploads/' . $upload_path . "/" . $xls_data['file_name'])) {
            // Cargando la hoja de c�lculo
            $Reader = new PHPExcel_Reader_Excel2007();
            $PHPExcel = $Reader->load('./uploads/pacientes/' . $xls_data['file_name']);

            $this->archivo_name = $xls_data['file_name'];
            // Asignar hoja de excel activa
            $PHPExcel->setActiveSheetIndex(0);
            $grado_list['grado_list'] = $this->generic_model->get('cliente_grado', null, 'id, nombre grado');
//            print_r($grado_list);


            for ($x = 2; $x <= $PHPExcel->getActiveSheet()->getHighestRow(); $x++) {

                $this->row_file = $x;

                $numero = get_value_xls($PHPExcel, 0, $x); // numero de archivo
                $cedula = get_value_xls($PHPExcel, 1, $x); // PersonaComercio_cedulaRuc
                $tarjeta = get_value_xls($PHPExcel, 2, $x); // codigo_issfa
                $tarifa = get_value_xls($PHPExcel, 3, $x); // clientetipo_idclientetipo

                $apellido = get_value_xls($PHPExcel, 5, $x); // apellido
                $nombre = get_value_xls($PHPExcel, 6, $x); // nombre

                $convenio = get_value_xls($PHPExcel, 8, $x); // aseguradoras_id
                $fecha_nac = get_fecha_xls($PHPExcel, 9, $x); //fecha nacimiento
                $sexo = get_value_xls($PHPExcel, 10, $x); // sexo_id
                $estado_civ = get_value_xls($PHPExcel, 11, $x); // estado_civil_id
                $fecha_aper = get_fecha_xls($PHPExcel, 12, $x); // fecha
                $ocupacion = get_value_xls($PHPExcel, 13, $x); // $ocupacion
                $prov_pac = get_value_xls($PHPExcel, 14, $x); // provincia_id
                $cant_pac = get_value_xls($PHPExcel, 15, $x); // canton_id
                $ciud_pac = get_value_xls($PHPExcel, 16, $x); // parroquia_id
                $calle_pac = get_value_xls($PHPExcel, 17, $x); // direccion
                $telef_pac = get_value_xls($PHPExcel, 18, $x); // telefonos
                $nomb_fam = get_value_xls($PHPExcel, 19, $x); // familiar_nombre
                $rela_fam = get_value_xls($PHPExcel, 20, $x); // familiar_parentesco

                $calle_fam = get_value_xls($PHPExcel, 24, $x); // familiar_direccion
                $telef_fam = get_value_xls($PHPExcel, 25, $x); // familiar_telefono
                $ci_tit = get_value_xls($PHPExcel, 26, $x); // ci_titular

                $siguni = get_value_xls($PHPExcel, 37, $x); // unidad_id => en caso de que el paciente sea militar
                $sigunit = get_value_xls($PHPExcel, 38, $x); // unidad_id => encaso de que elpaciente sea familairde militar

                $nomgra = get_value_xls($PHPExcel, 40, $x); // grado_id  => en caso de que el paciente sea  militar
                $nomgrat = get_value_xls($PHPExcel, 41, $x); // grado_id  => en caso de que el paciente sea familiar de militar

                $petnica = get_value_xls($PHPExcel, 44, $x); // etnia_id 
                $nacionalid = get_value_xls($PHPExcel, 45, $x); // nacionalidad_id 
                $afiess = get_value_xls($PHPExcel, 46, $x); // aseguradora_id => en caso de que convenio este vacio o el dato de convenio no sea un id valido con la tabla 
                $afissfa = get_value_xls($PHPExcel, 47, $x); // aseguradora_id => en caso de que convenio este vacio o el dato de convenio no sea un id valido con la tabla 
                $afispol = get_value_xls($PHPExcel, 48, $x); // aseguradora_id => en caso de que convenio este vacio o el dato de convenio no sea un id valido con la tabla 
                $afotros = get_value_xls($PHPExcel, 49, $x); // aseguradora_id => en caso de que convenio este vacio o el dato de convenio no sea un id valido con la tabla 
                $correo = get_value_xls($PHPExcel, 50, $x); // email
                // Valores a guardar en la tabla billiong_cliente
//                echo $fecha_nac." longitud ".strlen($fecha_nac);
                if (strlen(trim($fecha_nac)) < 11) {
                    $fecha_nac = '';
                }
                // LOS CAMPOS QUE EN EL EXCEL ESTAN VACIOS LOS REMPLAZO X EL STRING CAMPO VACIO 
                $nomgra = $this->campos_excel_vacios($nomgra, 'nomgra');
                $nomgrat = $this->campos_excel_vacios($nomgrat, 'nomgraT');

                //  A los valores que voya a envair comoaprametros que deben cambiar cada vez aqui le asigno el valor 
                $this->nacionalidad_id = $this->get_nacionalidadId($nacionalid, $this->nacionalidades_list, 'Nacionalidad');
                $this->provincia_id = $this->get_coincidencias($prov_pac, $this->provincias_list, $x, "Provincia");
                $this->nombre = $nombre;
                $this->apellido = $apellido;
                $this->fecha_nac = $this->verificar_fecha($fecha_nac);
                $this->num_archivo = $numero;


                $siguni = $this->campos_excel_vacios($siguni, 'siguni');
                $sigunit = $this->campos_excel_vacios($sigunit, 'sigunit');

                // Valido la cedula y asigno cedula ruc o pasaporte a la variable global para bque guarde en clientes
                $this->PersonaComercio_cedulaRuc = $this->validar_cedula_ruc($cedula);
                $data = array(
                    'PersonaComercio_cedulaRuc' => $this->PersonaComercio_cedulaRuc,
                    'es_pasaporte' => $this->es_pasaporte,
                    'nombres' => $nombre,
                    'apellidos' => $apellido,
                    'direccion' => $calle_pac,
                    'telefonos' => $telef_pac,
                    'fecha' => $this->verificar_fecha($fecha_aper),
                    'num_archivo' => $numero,
                    'user_id' => $this->user->id,
//                    'fecha_nacimiento'=>  $this->verificar_fecha($fecha_nac),
                    'fecha_nacimiento' => $this->fecha_nac,
                    'ocupacion' => $ocupacion,
                    'familiar_nombre' => $nomb_fam,
                    'familiar_parentesco' => $rela_fam,
                    'familiar_direccion' => $calle_fam,
                    'familiar_telefono' => $telef_fam,
                    'codigo_issfa' => $tarjeta,
                    'ci_titular' => $ci_tit,
                    'clientetipo_idclientetipo' => $tarifa,
                    'email' => $correo,
                    'etnia_id' => $this->validar_etnia($petnica, $this->etnia_list),
                    'aseguradora_id' => $this->get_aseguradoraId($convenio, $afiess, $afissfa, $afispol, $afotros),
                    // Crear funcion del 1 - 8 los pares pasivos y los impares son activos
                    // primero pasar que tarifa nos ea mayor a 8 
                    'estado_id' => $this->ver_estado_militar($tarifa),
                    // la funcion solo se le especifica si es grado o unidad
                    'grado_id' => $this->get_grado_id_unidad_id($tarifa, $this->grado_list, $nomgra, $nomgrat
                            , null, null, null, 1, $x),
                    'unidad_id' => $this->get_grado_id_unidad_id($tarifa, null, null, null
                            , $this->unidades_list, $siguni, $sigunit, 0, $x),
//                    'nacionalidad_id'=>  $this->get_nacionalidadId($nacionalid, $this->nacionalidades_list, 'Nacionalidad'),
                    'nacionalidad_id' => $this->nacionalidad_id,
                    'sexo_id' => $this->get_sexoId($sexo, $this->sexo_list, 'Sexo del paciente'),
                    'estado_civil_id' => $this->get_estadoCivilId($estado_civ, $this->estado_civil_list, 'Estado Civil'),
//                    'provincia_id'=>  $this->get_coincidencias($prov_pac, $this->provincias_list, $x,"Provincia"),
                    'provincia_id' => $this->provincia_id,
                    'canton_id' => $this->get_coincidencias($cant_pac, $this->cantones_list, $x, "Canton"),
                    'parroquia_id' => $this->get_coincidencias($ciud_pac, $this->parroquias_list, $x, "Parroquia"),
                );
//                print_r($data);
//                if ($this->cheque_existe($nro, $banco_id)) {
//                    echo warning_msg('Ha ocurrido un problema al grabar');
//                    die();
//                    break;
//                }
//                //Guardar values en la BD
                $save_paciente = $this->generic_model->save($data, 'billing_cliente_copy1');
//                echo $save_cheque;
                if ($save_paciente <= 0) {
                    echo warning_msg('Ha ocurrido un problema al grabar');
                    $this->db->trans_rollback();
                    die();
                }
            }
        } else {
            echo error_info_msg('No se ha podido cargar el archivo .xlsx');
            $this->db->trans_rollback();
            die();
        }

        /* Finalizamos la transaccion */
//        if ($this->db->trans_status() === FALSE) {
//            $this->db->trans_rollback();
//        } else {
//            $this->db->trans_commit();
//        }
    }

    function cliente_existe($ci) {

        $existe = $this->generic_model->count_all_results('billing_cliente', array('PersonaComercio_cedulaRuc' => $ci));
        if ($existe > 0 OR empty($ci)) {
            return true;
        } else {
            echo error_info_msg(' El cliente con C.I/RUC. ' . $ci . ' no existe, debe crearlo antes de registrar un cheque a su nombre');
            $this->db->trans_rollback();
            die();
            return false;
        }
    }

    function cheque_existe($num_cheque, $banco_id) {
        $id_cheque = $this->generic_model->get_val_where('bill_cheque_pago', array('nro' => $num_cheque, 'banco_id' => $banco_id), 'id');
        if ($id_cheque > 0) {
            echo warning_msg('Cheque ' . $num_cheque . ' ya existe');
            $this->db->trans_rollback();
            return true;
        } else {
//            echo success_msg('<br>Cheque ' . $num_cheque . ' se ha registrado');

            return false;
        }
    }

// CADENA, LISTA, NUMERO DE FILADEL DOCUMENTOEXCEL, NOMBRE DELCAMPO DELDOCUMENTO EXCEL
    function get_coincidencias($string, $list, $num_archivo, $subject = '', $coincidencia_mitad = 0, $val_return = '-1', $tabla = '') {
//        print_r($list);
        $string = trim($string);
        $encontrado = false;
        //  id para sacar el registro decoincidencias intermedias
        $id_reg = 0;
//        echo tagcontent('script', '$("#p_subject").text("' . $subject . '")');
//Si esta vacio retornamos -1
        if (empty($string)) {
            return '-1';
        }

//                echo tagcontent('script', 'console.log("'.$string.'");');
//            print_r($string);
        $aux = 0;
        $id_reg = 0;
        foreach ($list as $value) {
//            echo tagcontent('script', '$("#p_id").text("' . $value->id . '")');
//                echo tagcontent('script', 'console.log("'.$value->nombre.'");');
            // estas comparacion s ela hará solo para la datos que las coincidencias no estan ni al inicio ni al fin ni tienen la misma longitud
            if ($coincidencia_mitad == 1) {
                $array_concidencias_string = explode($string, $value->nombre);

                if (sizeof($array_concidencias_string) > 1) {
//                    echo "<br>CAdena de la lista ".$value->nombre."  Id:  ".$value->id;
                    $aux = $value->id;
                    if ($aux > $id_reg) {
                        $id_reg = $aux;
                        $encontrado = true;
                    }
                }
            } elseif ($coincidencia_mitad == 0) {
                if (substr_compare($string, $value->nombre, 0, strlen($string), true) == 0) {
                    $id_reg = $value->id;
                    $encontrado = true;
                    break;
                }
            }
        }
//        echo "<br><h1>hay coincidencia</h1><br>".$id_reg;
//        die();
        if ($encontrado) {
            return $id_reg;
        } else {

            // OJO para unidad va -2
            echo error_info_msg('El string "' . $string . '" de ' . $subject . ' no se encuentra registrado en el sistema, o el nombre no coincide');

//            $this->db->trans_rollback();
//            die();
            /* FUNCION PARA GUARDAR INCIDENTES QUE NO SE PUDIERON GRABAR */
            $this->save_incidentes($string, $num_archivo, $subject, $this->archivo_name, $tabla, $this->num_archivo);
            return $val_return;
        }
    }

    function get_nacionalidadId($string, $list, $subject = '') {
//        print_r($list);
//        die();

        $string = trim($string);
        $string = substr($string, 0, 3);
        $encontrado = false;
        echo tagcontent('script', '$("#p_subject").text("' . $subject . '")');

        if (empty($string)) {
            return '-1';
        }

        foreach ($list as $value) {
            echo tagcontent('script', '$("#p_id").text("' . $value->id . '")');
            if (substr_compare($string, $value->nombre, 0, strlen($string), true) == 0) {
                $encontrado = true;
                break;
            }
        }

        if ($encontrado) {
//            echo 'id = ' . $value->id;
            return $value->id;
        } else {
            return '-1';
        }
    }

    function get_sexoId($string, $list, $subject = '') {
//        print_r($list);
        $string = trim($string);
        $encontrado = false;
        echo tagcontent('script', '$("#p_subject").text("' . $subject . '")');

        //Si esta vacio retornamos -1
        if (empty($string)) {
            return '-1';
        }

        foreach ($list as $value) {
            echo tagcontent('script', '$("#p_id").text("' . $value->id . '")');
            if (substr_compare($string, $value->nombre, 0, strlen($string), true) == 0) {
                $encontrado = true;

                break;
            }
        }

        if ($encontrado) {
//            echo 'id = ' . $value->id;
            return $value->id;
        } else {
            return '-1';
        }
    }

    function get_estadoCivilId($string, $list, $subject = '') {
//        print_r($list);
        $string = trim($string);
        $encontrado = false;
        echo tagcontent('script', '$("#p_subject").text("' . $subject . '")');

        //Si esta vacio retornamos -1
        if (empty($string)) {
            return '-1';
        }

        foreach ($list as $value) {
            echo tagcontent('script', '$("#p_id").text("' . $value->id . '")');
            if (substr_compare($string, $value->nombre, 0, strlen($string), true) == 0) {
                $encontrado = true;

                break;
            }
        }

        if ($encontrado) {
//            echo 'id = ' . $value->id;
            return $value->id;
        } else {
            return '-1';
        }
    }

    /* Guarda los strings que no se pudieron guardar por mala digitacion del usuario, para tener respaldo.  JLQ */

    function save_incidentes($string, $num_fila_excel, $subject, $archivo_name, $tabla, $num_archivo) {
        $data = array(
            'string' => $string,
            'id_paciente_row_file' => $num_fila_excel,
            'campo' => $subject,
            'nombre_archivo' => $archivo_name,
            'nom_tab_lista' => $tabla,
            'num_archivo' => $num_archivo,
        );
        $this->generic_model->save($data, 'incidentes_importacion');
    }

    /* Extrae la aseguradora segun los campos del excel. JLQ */

    function get_aseguradoraId($convenio_id, $es_iess, $es_issfa, $es_isspol, $es_otros) {
        $es_issfa = trim($es_issfa);
        $es_isspol = trim($es_isspol);
        $es_otros = trim($es_otros);
        //Si el convenio coincide del 1 - 9, se graba directo el id
        if ($convenio_id >= 1 && $convenio_id <= 9) {
            return $convenio_id;
        }
        //Si esta vacio buscamos en los campos restantes
        else if (empty($convenio_id)) {
            if ($es_iess == 'VERDADERO') {
                return '3'; //Id 3: IESS, seguro voluntario
            } else if ($es_issfa == 'VERDADERO') {
                return '1'; //Id 1: Seguro ISSFA
            } else if ($es_isspol == 'VERDADERO') {
                return '2'; //Id 2: Seguro ISSPOL
            } else if ($es_otros == 'VERDADERO') {
                return '-1'; //Id -1: Otros seguros
            } else {//Si no coincide ninguno enviamos -2: NINGUNA
                return '-2';
            }
        }
    }

    //del 1 - 8 los pares pasivos y los impares son activos
    // primero pasar que tarifa nos ea mayor a 8 
    function ver_estado_militar($id_tipocliente) {
        if ($id_tipocliente <= 8) {
            if ($id_tipocliente % 2 == 0) {
//                echo "Pasivo";
                return 2;
            } else {
//                echo "Activo";
                return 1;
            }
        } else {
            switch ($id_tipocliente) {
                case 10:
//                echo "Activo";
                    return 1;
                    break;

                default:
                    $this->save_incidentes($id_tipocliente, $this->row_file, 'tarifa', $this->archivo_name, "billing_clientetipo", $this->num_archivo);
                    return '-1';
                    break;
            }
        }
    }

    // saco  el grado delpaciente si es militar saco del campo excel nomgra y si es familiar saco del nomgrat
    // Tambien de esta funcion saco la unidad si es militar saco del campo excel siguni y si es familiar saco del campo sigunit
    // para estos se salen mas de una cooncidencia se tomará la ultima y en caso de nada se envia -2
    function get_grado_id_unidad_id($tarifa, $grado_list = null, $nomgra = null, $nomgrat = null
    , $unidad_list = null, $siguni = null, $sigunit = null, $es_grado = 0/* si esta en sero significa q es unidad */, $num_arch_excel = 0) {

        $nomgra = trim($nomgra);
        $nomgrat = trim($nomgrat);

        $siguni = trim($siguni);
        $sigunit = trim($sigunit);

        $tab_grado = "cliente_grado";
        $tab_unidad = "unidad_ffaa";
        switch ($tarifa) {
            // MILITARES
            case 1:
                if ($es_grado == 1) {
                    // saca el grado
                    $id_grado = $this->get_coincidencias($nomgra, $grado_list, $num_arch_excel, 'campo nomgra', 0, '-1', $tab_grado);
                    return $id_grado;
                } else {
                    // saca la unidad
                    $id_unidad = $this->get_coincidencias($siguni, $unidad_list, $num_arch_excel, 'campo siguni', 1, '-2', $tab_unidad);
                    return $id_unidad;
                }

                break;
            case 2:
                if ($es_grado == 1) {
                    // saca el grado
                    $id_grado = $this->get_coincidencias($nomgra, $grado_list, $num_arch_excel, 'campo nomgra', 0, '-1', $tab_grado);
                    return $id_grado;
                } else {
                    // saca la unidad
                    $id_unidad = $this->get_coincidencias($siguni, $unidad_list, $num_arch_excel, 'campo siguni', 1, '-2', $tab_unidad);
                    return $id_unidad;
                }

                break;
            case 10:
                if ($es_grado == 1) {
                    // saca el grado
                    $id_grado = $this->get_coincidencias($nomgra, $grado_list, $num_arch_excel, 'campo nomgra', 0, '-1', $tab_grado);
                    return $id_grado;
                } else {
                    // saca la unidad
                    $id_unidad = $this->get_coincidencias($siguni, $unidad_list, $num_arch_excel, 'campo siguni', 1, '-2', $tab_unidad);
                    return $id_unidad;
                }

                break;
            case 11:
                if ($es_grado == 1) {
                    // saca el grado
                    $id_grado = $this->get_coincidencias($nomgra, $grado_list, $num_arch_excel, 'campo nomgra', 0, '-1', $tab_grado);
                    return $id_grado;
                } else {
                    // saca la unidad
                    $id_unidad = $this->get_coincidencias($siguni, $unidad_list, $num_arch_excel, 'campo siguni', 1, '-2', $tab_unidad);
                    return $id_unidad;
                }

                break;
            case 12:
                if ($es_grado == 1) {
                    // saca el grado
                    $id_grado = $this->get_coincidencias($nomgra, $grado_list, $num_arch_excel, 'campo nomgra', 0, '-1', $tab_grado);
                    return $id_grado;
                } else {
                    // saca la unidad
                    $id_unidad = $this->get_coincidencias($siguni, $unidad_list, $num_arch_excel, 'campo siguni', 1, '-2', $tab_unidad);
                    return $id_unidad;
                }

                break;

            // FAMILIARES DE MILITAR
            case 3:
                if ($es_grado == 1) {
                    // saco elgrado del familair
                    $id_grado = $this->get_coincidencias($nomgrat, $grado_list, $num_arch_excel, 'campo nomgrat', 0, '-1', $tab_grado);
                    return $id_grado;
                } else {
                    // saco la unidad del familiar
                    $id_unidad = $this->get_coincidencias($sigunit, $unidad_list, $num_arch_excel, 'campo sigunit', 1, '-2', $tab_unidad);
                    return $id_unidad;
                }

                break;
            case 4:
                if ($es_grado == 1) {
                    // saco elgrado del familair
                    $id_grado = $this->get_coincidencias($nomgrat, $grado_list, $num_arch_excel, 'campo nomgrat', 0, '-1', $tab_grado);
                    return $id_grado;
                } else {
                    // saco la unidad del familiar
                    $id_unidad = $this->get_coincidencias($sigunit, $unidad_list, $num_arch_excel, 'campo sigunit', 1, '-2', $tab_unidad);
                    return $id_unidad;
                }

                break;
            case 5:
                if ($es_grado == 1) {
                    // saco elgrado del familair
                    $id_grado = $this->get_coincidencias($nomgrat, $grado_list, $num_arch_excel, 'campo nomgrat', 0, '-1', $tab_grado);
                    return $id_grado;
                } else {
                    // saco la unidad del familiar
                    $id_unidad = $this->get_coincidencias($sigunit, $unidad_list, $num_arch_excel, 'campo sigunit', 1, '-2', $tab_unidad);
                    return $id_unidad;
                }

                break;
            case 6:
                if ($es_grado == 1) {
                    // saco elgrado del familair
                    $id_grado = $this->get_coincidencias($nomgrat, $grado_list, $num_arch_excel, 'campo nomgrat', 0, '-1', $tab_grado);
                    return $id_grado;
                } else {
                    // saco la unidad del familiar
                    $id_unidad = $this->get_coincidencias($sigunit, $unidad_list, $num_arch_excel, 'campo sigunit', 1, '-2', $tab_unidad);
                    return $id_unidad;
                }

                break;
            case 7:
                if ($es_grado == 1) {
                    // saco elgrado del familair
                    $id_grado = $this->get_coincidencias($nomgrat, $grado_list, $num_arch_excel, 'campo nomgrat', 0, '-1', $tab_grado);
                    return $id_grado;
                } else {
                    // saco la unidad del familiar
                    $id_unidad = $this->get_coincidencias($sigunit, $unidad_list, $num_arch_excel, 'campo sigunit', 1, '-2', $tab_unidad);
                    return $id_unidad;
                }

                break;
            case 8:
                if ($es_grado == 1) {
                    // saco elgrado del familair
                    $id_grado = $this->get_coincidencias($nomgrat, $grado_list, $num_arch_excel, 'campo nomgrat', 0, '-1', $tab_grado);
                    return $id_grado;
                } else {
                    // saco la unidad del familiar
                    $id_unidad = $this->get_coincidencias($sigunit, $unidad_list, $num_arch_excel, 'campo sigunit', 1, '-2', $tab_unidad);
                    return $id_unidad;
                }

                break;
            case 9:
                if ($es_grado == 1) {
                    // saco elgrado del familair
                    $id_grado = $this->get_coincidencias($nomgrat, $grado_list, $num_arch_excel, 'campo nomgrat', 0, '-1', $tab_grado);
                    return $id_grado;
                } else {
                    // saco la unidad del familiar
                    $id_unidad = $this->get_coincidencias($sigunit, $unidad_list, $num_arch_excel, 'campo sigunit', 1, '-2', $tab_unidad);
                    return $id_unidad;
                }

                break;
            case 13:
                if ($es_grado == 1) {
                    // saco elgrado del familair
                    $id_grado = $this->get_coincidencias($nomgrat, $grado_list, $num_arch_excel, 'campo nomgrat', 0, '-1', $tab_grado);
                    return $id_grado;
                } else {
                    // saco la unidad del familiar
                    $id_unidad = $this->get_coincidencias($sigunit, $unidad_list, $num_arch_excel, 'campo sigunit', 1, '-2', $tab_unidad);
                    return $id_unidad;
                }

                break;

            default:
                break;
        }
    }

    // verifico si la fecha esta en un formato dia-sep-12 si esta cuakquier otro valor remplazo x null o empty (aun x definir )
    function verificar_fecha($string) {
        $array_fecha = explode('-', $string);
//        print_r($string);
//        echo "<br>";
        if (sizeof($array_fecha) > 1) {
            $fecha = date_format(date_create($string), 'Y-m-d');
            return $fecha;
        } else {
            return $fecha = '';
        }

//        echo $fecha_nac;
    }

    // Validamos la etnia si es un dato de labase de datos 
    function validar_etnia($petnica, $lista_etnia) {
        $petnica = trim($petnica);

        foreach ($lista_etnia as $key => $value) {
            if ($value->id == $petnica) {
                return $value->id;
            } else {
                return '-1';
            }
        }
    }

    function campos_excel_vacios($string, $nombre_campo) {
        if (empty($string)) {
            $string = 'CAMPO ' . $nombre_campo . ' EXCEL VACIO';
        } else {
            $string = $string;
        }
        return $string;
    }

    /* Generar el codigo HC para los pacientes que no tienen cedula */

    public function get_nuhc($nombres, $apellidos, $provincia_id, $nacionalidad, $fecha_nac) {
        $codigo_nuhc = '';

        /* Separamos los NOMBRES, primero y segundo */
        $primer_nombre = $this->quitar_tildes($this->get_primer_nombre($nombres));
        $segundo_nombre = $this->quitar_tildes($this->get_segundo_nombre($nombres));

        /* Separamos los APELLIDOS, primero y segundo */
        $primer_apellido = $this->quitar_tildes($this->get_primer_apellido($apellidos));
        $segundo_apellido = $this->quitar_tildes($this->get_segundo_apellido($apellidos));

//        print_r($array_nombre);

        $siglas_nombres = substr($primer_nombre, 0, 2); //1 y 2 caracter: dos primeras siglas del primer nombre
        $siglas_nombres .= substr($segundo_nombre, 0, 1); //3 caracter: primera letra del segundo nombre
        $siglas_nombres .= substr($primer_apellido, 0, 2); //4 y 5 caracter: dos primeras letras del primer apellido
        $siglas_nombres .= substr($segundo_apellido, 0, 1); //6 caracter: primera letra del primer apellido
        //
        //Si provincia es -1 (vacio) se pone provincia_id a 0, para que lo tome como indice valido del array
        if ($provincia_id == -1) {
            $provincia_id = 0;
        }
        /* Codigo de provincia extraido de bd */
        //Si es extranjero se pone 99
        if ($nacionalidad != 1) {
            $codigo_provincia = '99';
        } else if($nacionalidad != '-1') {
//            print_r($this->provincias_list);
//            echo '<br>Provincia id: '.$this->provincia_id;
//            echo '<br>Codigo: ';
            $codigo_provincia = $this->provincias_list[$provincia_id]->codigo;
        }else{
            $codigo_provincia = $this->provincias_list[$provincia_id]->codigo;
            
        }

        if (empty($fecha_nac)) {
            /* Anio de nacimiento 4 caracteres */
            $anio_nac = '0000';
            /* Mes de nacimiento 4 caracteres */
            $mes_nac = '00';
            /* Dia de nacimiento 4 caracteres */
            $dia_nac = '00';
        } else {

            /* Anio de nacimiento 4 caracteres */
            $anio_nac = date('Y', strtotime($fecha_nac));
            /* Mes de nacimiento 4 caracteres */
            $mes_nac = date('m', strtotime($fecha_nac));
            /* Dia de nacimiento 4 caracteres */
            $dia_nac = date('d', strtotime($fecha_nac));
        }
        echo $fecha_nac;
        echo date('Y-m-d', strtotime($fecha_nac));
        echo '<br>';
        /* Decada de nacimiento */
        $control = substr($anio_nac, 2, 1);

        /* Estructuramos el codigo */
        $codigo_nuhc = strtoupper($siglas_nombres) . $codigo_provincia . $anio_nac . $mes_nac . $dia_nac . $control;

        return $codigo_nuhc;
    }

    /* El codigo nuch requiere que se quiten las tildes de los caracteres de nombres y apellidos */

    function quitar_tildes($cadena) {
        $no_permitidas = array("á", "é", "í", "ó", "ú", "Á", "É", "Í", "Ó", "Ú", "ñ", "À", "Ã", "Ì", "Ò", "Ù", "Ã™", "Ã ", "Ã¨", "Ã¬", "Ã²", "Ã¹", "ç", "Ç", "Ã¢", "ê", "Ã®", "Ã´", "Ã»", "Ã‚", "ÃŠ", "ÃŽ", "Ã”", "Ã›", "ü", "Ã¶", "Ã–", "Ã¯", "Ã¤", "«", "Ò", "Ã", "Ã„", "Ã‹");
        $permitidas = array("a", "e", "i", "o", "u", "A", "E", "I", "O", "U", "n", "N", "A", "E", "I", "O", "U", "a", "e", "i", "o", "u", "c", "C", "a", "e", "i", "o", "u", "A", "E", "I", "O", "U", "u", "o", "O", "i", "a", "e", "U", "I", "A", "E");
        $texto = str_replace($no_permitidas, $permitidas, $cadena);
        return $texto;
    }

    function get_primer_nombre($nombres) {
        $array_nombre = explode(chr(32), $nombres);
        return $primer_nombre = $array_nombre[0];
    }

    function get_segundo_nombre($nombres) {
        $array_nombre = explode(chr(32), $nombres);

        //Si no tiene segundo nombre se guarda 0
        if (sizeof($array_nombre) == 1) {
            $segundo_nombre = '0';
        } else if (sizeof($array_nombre) == 2) {//si tiene 2 nombres
            $segundo_nombre = $array_nombre[1];
        } else if (sizeof($array_nombre) == 3) {//si tiene 3 nombres (ej. Andres del cisne)
            $segundo_nombre = $array_nombre[1] . ' ' . $array_nombre[2];
        }else{
            $segundo_nombre = $array_nombre[1];
        }
        return $segundo_nombre;
    }

    function get_primer_apellido($apellidos) {
        $array_apellido = explode(chr(32), $apellidos);
        return $primer_apellido = $array_apellido[0];
    }

    function get_segundo_apellido($apellidos) {
        $array_apellido = explode(chr(32), $apellidos);
        //Si no tiene segundo apellido se guarda 0
        if (sizeof($array_apellido) == 1) {
            $segundo_apellido = '0';
        } else {
            return $segundo_apellido = $array_apellido[1];
        }
    }

    // Validar cedula o ruc 
    function validar_cedula_ruc($clienteID) {
        $cedRuc_valida = $this->docident->validarCedula($clienteID);
        if ($cedRuc_valida == false) {
            // valido q si no fue correcta la cedula valide por ruc 
            $cedRuc_valida = $this->docident->validarRucPersonaNatural($clienteID);
            if ($cedRuc_valida == false) {
//                    echo tagcontent('script', 'alertaError(" Cédula o Ruc Invalida")');
//                    die();
                // VALIDO SI TIENE DATOS SI TIENE DATOS ES PASAPORTE
                if (empty($clienteID)) {
                    // Lalamo a funciond e generar codigo nuhc
                    $this->es_pasaporte = 1;
                    $clienteID = $this->get_nuhc($this->nombre, $this->apellido, $this->provincia_id, $this->nacionalidad_id, $this->fecha_nac); //se envia las variables globales que necesite para esta funcion 
                    return $clienteID;
                } else {
                    // validar que no tenga solo ceros 
                    $cont = $this->contar_n_caracteres($clienteID,0);
                    if($cont == strlen($clienteID)){
                        $clienteID = $this->get_nuhc($this->nombre, $this->apellido, $this->provincia_id, $this->nacionalidad_id, $this->fecha_nac); //se envia las variables globales que necesite para esta funcion 
                    }else{
                        $caracteres_nopermitidos = $this->validar_caracteres_permitidos($clienteID);
                        if($caracteres_nopermitidos == false){
                            $clienteID = $this->get_nuhc($this->nombre, $this->apellido, $this->provincia_id, $this->nacionalidad_id, $this->fecha_nac); //se envia las variables globales que necesite para esta funcion 
                        }
                    }
                    $this->es_pasaporte = 1;
                    return $clienteID;
                }
            } else {
                $this->es_pasaporte = 0;
                return $clienteID;
            }
        } else {
            // Es cedula
            $this->es_pasaporte = 0;
            return $clienteID;
        }
    }
    
    function contar_n_caracteres($string,$caracter) {
//        echo $string."<br>";
        $cont = 0;
        foreach (count_chars($string, 1) as $key => $value) {
//            echo "<br>se encontro ".$value." veces de el caracter ". chr($key);
            if(chr($key) == $caracter){
                $cont = $value;
            }
        }
//        echo "<br>Cont ".$cont;
//        echo "<br>Longitud ".  strlen($string);
        
        return $cont;
    }
    
    function validar_caracteres_permitidos($string){
        if (preg_match("/^[a-zA-Z0-9\-_]{1,}$/", $string)) { 
//          echo "El nombre de usuario $nombre_usuario es correcto<br>"; 
          return true; 
       } else { 
//           echo "El nombre de usuario $nombre_usuario no es válido<br>"; 
          return false; 
       }
    }

}
