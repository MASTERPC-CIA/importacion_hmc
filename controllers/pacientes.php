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

   function index() {
        $res['title'] = 'Importar Pacientes';
        $res['view'] = $this->load->view('pacientes_view', '', TRUE);
        $res['slidebar'] = $this->load->view('slidebar_lte', '', TRUE);
        $this->load->view('common/templates/dashboard_lte', $res);
    }

    function importar() {
        $upload_path = 'pacientes';
//        $this->get_idbanco($nombre_banco);
        $this->loadfromfile($upload_path);
    }


    public function loadfromfile($upload_path){
         set_time_limit(0);
        $this->load->library('excel');
        $this->load->library('docident');
        $this->load->library('common/cuentasxpagar');
        $this->load->helper('date_helper');
            
        $config['upload_path'] = './uploads/'.$upload_path;
        $config['allowed_types'] = 'xlsx';
        $config['max_size']	= '0';
        $config['max_width']  = '0';
        $config['max_height']  = '0';

        $this->load->library('upload', $config);

        if ( ! $this->upload->do_upload())
        {
            $error = $this->upload->display_errors();
            echo tagcontent('strong', $error, array('class'=>'text-danger font20'));
            die();    
        }
        else
        {
            $upl_data = $this->upload->data();
        }

        $upl_data = $this->upload->data();
        
        $this->get_pacientesdata_xls($upl_data,$upload_path);
         
            /* **Finalizamos la transaccion** */
            if ($this->db->trans_status() === FALSE)
            {
                echo warning_msg(' Ha ocurrido un problema, no se pudo completar la transaccion.');                
                $this->db->trans_rollback();
            }
            else
            {
                echo success_msg(' EL PROCESO A TERMINADO CON EXITO');
                $this->db->trans_commit();
            }
                
                echo tagcontent('strong', 'Se ha terminado de cargar el listado de clientes', array('class'=>'text-success font20'));
            
    }
    function get_pacientesdata_xls($xls_data,$upload_path) {
        if (file_exists('./uploads/'.$upload_path."/" . $xls_data['file_name'])) {
            // Cargando la hoja de c�lculo
            $Reader = new PHPExcel_Reader_Excel2007();
            $PHPExcel = $Reader->load('./uploads/pacientes/' . $xls_data['file_name']);
            // Asignar hoja de excel activa
            $PHPExcel->setActiveSheetIndex(0);
            $bancos_list['data'] = $this->generic_model->get('billing_banco', array('id >' => '0'), 'id, nombre banco');
            $grado_list['grado_list'] = $this->generic_model->get('cliente_grado',null, 'id, nombre grado');
//            print_r($grado_list);


            for ($x = 2; $x <= $PHPExcel->getActiveSheet()->getHighestRow(); $x++) {

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
                $nomb_fam = get_value_xls($PHPExcel, 19, $x); // familair_nombre
                $rela_fam = get_value_xls($PHPExcel, 20, $x); // familair_parentesco
                
                $calle_fam = get_value_xls($PHPExcel, 24, $x); // familair_direccion
                $telef_fam = get_value_xls($PHPExcel, 25, $x); // familair_telefono
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
                if(strlen(trim($fecha_nac)) <11){
                    $fecha_nac='';
                }
                $data = array(
                    'PersonaComercio_cedulaRuc'=>$cedula,
                    'nombres'=>$nombre,
                    'apellidos'=>$apellido,
                    'direccion'=>$calle_pac,
                    'telefonos'=>$telef_pac,
                    'fecha'=>$fecha_aper,
                    'num_archivo'=>$numero,
                    'user_id'=>  $this->user->id,
                    'fecha_nacimiento'=>date_format(date_create($fecha_nac), 'Y-m-d'),
                    'ocupacion'=>  $ocupacion,
                    'familiar_nombre'=>  $nomb_fam,
                    'familiar_parentesco'=>  $rela_fam,
                    'familiar_direccion'=>  $calle_fam,
                    'familiar_telefono'=>  $telef_fam,
                    'familiar_telefono'=>  $telef_fam,
//                    'estado_id'=> $this->ver_estado_militar($tarifa),// Crear funcion del 1 - 8 los pares pasivos y los impares son activos
                    // primero pasar que tarifa nos ea mayor a 8 
                    
                );
                print_r($data);
//                if ($this->cheque_existe($nro, $banco_id)) {
//                    echo warning_msg('Ha ocurrido un problema al grabar');
//                    die();
//                    break;
//                }
//                //Guardar values en la BD
//                $save_cheque = $this->generic_model->save($values_cheque, 'bill_cheque_pago');
////                echo $save_cheque;
//                if ($save_cheque <= 0) {
//                    echo warning_msg('Ha ocurrido un problema al grabar');
//                    $this->db->trans_rollback();
//                    die();
//                }
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
// Funcion de busqueda de coincidencias por medio de un array 
    function get_idbanco($nombre_banco, $bancos_list) {
//        print_r($bancos_list['data']);

        foreach ($bancos_list['data'] as $value) {
//            echo '<br> ' . $value->id . ' ' . $value->banco;
//            echo '<br>BAnco: ' . $nombre_banco;
            echo '<br>'.strcmp($nombre_banco, $value->banco);
//            substr_compare ($cadena1 , $cadena2 , 0, strlen($cadena1), true);
            if (strcmp(substr_compare ($nombre_banco , $value->banco , 0, strlen($nombre_banco), true)) == 0) {
//                echo '<br>Encontrado, ID: ' . $value->id;
                return $value->id;
            } else {
                echo error_info_msg('El banco "' . $nombre_banco . '" no se encuentra registrado en el sistema, o el nombre no coincide');
                $this->db->trans_rollback();
                die();
            }
        }
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


    
    

}