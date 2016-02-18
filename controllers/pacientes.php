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
    private $grado_list;
    private $unidades_list;

    function __construct() {
        parent::__construct();
        //NOTA  Nacionalidades tiene un formato de registro diferente en el excel
        $this->nacionalidades_list = $this->generic_model->get('nacionalidad', array('id >' => '0', 'id <' => '5'), 'id, SUBSTRING(nombre,1,3)nombre');
        //NOTA  Provincias con Ñ mayuscula no verifica coincidencias
        $this->provincias_list = $this->generic_model->get('bill_provincia', array('idProvincia >' => '0'), 'idProvincia id, descripProv nombre');
        $this->cantones_list = $this->generic_model->get('bill_canton', array('idCanton >' => '0'), 'idCanton id, descripCtn nombre');
        $this->parroquias_list = $this->generic_model->get('bill_parroquia', array('idParroquia >' => '0'), 'idParroquia id, descripPq nombre');
        //NOTA se requiere nueva funcion para extraer id sexo
        $this->sexo_list = $this->generic_model->get('cliente_sexo', array('id >' => '0'), 'id, nombre');
        //NOTA se requiere nueva funcion para extraer id estado civil
        $this->estado_civil_list = $this->generic_model->get('cliente_estado_civil', array('id >' => '0'), 'id, nombre');
        $this->grado_list = $this->generic_model->get('cliente_grado', array('id >' => '0'), 'id, nombre');
        //NOTA se requiere nueva funcion para extraer id unidad
        $this->unidades_list = $this->generic_model->get('unidad_ffaa', array('id >' => '0'), 'id, uni_nombre_abr nombre');
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
        $this->get_coincidencias($string, $this->parroquias_list, 'Parroquias');
    }

    function importar1() {
        $upload_path = './uploads/pacientes';
//        $this->get_idbanco($nombre_banco);
        $this->loadfromfile($upload_path);
    }

    function loadfromfile($upload_path) {
//        echo 'Ruta: '.$ruta;
        set_time_limit(0);
        $this->load->library('excel');

        $this->db->trans_begin();

//        $this->load->model('client_model');
        $config['max_height'] = '0';

        $config['upload_path'] = $upload_path;
        $config['allowed_types'] = 'xlsx';
        $config['max_size'] = '0';
        $config['max_width'] = '0';

        $this->load->library('upload', $config);

        if (!$this->upload->do_upload()) {
            echo 'no file ';
            $error = $this->upload->display_errors();
            echo error_info_msg($error);
            $this->db->trans_rollback();

//            echo tagcontent('strong', $error, array('class' => 'text-danger font20'));
            die();
        } else {
            $upl_data = $this->upload->data();
            echo info_msg(': El archivo es correcto');
        }

        $upl_data = $this->upload->data();
//        $upl_data['file_name'];
        $this->get_pacientesdata_xls($upl_data);
    }

    function get_pacientesdata_xls($xls_data) {
        if (file_exists('./uploads/pacientes/' . $xls_data['file_name'])) {
            // Cargando la hoja de c�lculo
            $Reader = new PHPExcel_Reader_Excel2007();
            $PHPExcel = $Reader->load('./uploads/pacientes/' . $xls_data['file_name']);
            // Asignar hoja de excel activa
            $PHPExcel->setActiveSheetIndex(0);
            $bancos_list['data'] = $this->generic_model->get('billing_banco', array('id >' => '0'), 'id, nombre banco');


            for ($x = 2; $x <= $PHPExcel->getActiveSheet()->getHighestRow(); $x++) {

                $beneficiario = get_value_xls($PHPExcel, 0, $x);
                $fecha_emision = get_value_xls($PHPExcel, 1, $x);
                $timestamp = PHPExcel_Shared_Date::ExcelToPHP($fecha_emision);//Formateamos la fecha de xls a php
                $fecha_php = date("Y-m-d", $timestamp);
                $fecha_emision_php = date('Y-m-d', strtotime("$fecha_php + 1 day"));

                $fecha_cobro = get_value_xls($PHPExcel, 2, $x);
                $timestamp = PHPExcel_Shared_Date::ExcelToPHP($fecha_cobro);//Formateamos la fecha de xls a php
                $fecha_php = date("Y-m-d", $timestamp);
                $fecha_cobro_php = date('Y-m-d', strtotime("$fecha_php + 1 day"));

                $nro = get_value_xls($PHPExcel, 3, $x);
                $valor = get_value_xls($PHPExcel, 4, $x);
                $banco_id = $this->get_idbanco(get_value_xls($PHPExcel, 5, $x), $bancos_list);

                $values_cheque = array(
                    'nro' => $nro,
                    'fecha_emision' => $fecha_emision_php,
                    'fecha_cobro' => $fecha_cobro_php,
                    'lugar' => 'Loja',
                    'nombre_beneficiario' => $beneficiario,
                    'banco_id' => $banco_id,
                    'valor' => $valor,
                );

                if ($this->cheque_existe($nro, $banco_id)) {
                    echo warning_msg('Ha ocurrido un problema al grabar');
                    die();
                    break;
                }
                //Guardar values en la BD
                $save_cheque = $this->generic_model->save($values_cheque, 'bill_cheque_pago');
//                echo $save_cheque;
                if ($save_cheque <= 0) {
                    echo warning_msg('Ha ocurrido un problema al grabar');
                    $this->db->trans_rollback();
                    die();
                }
            }
        } else {
            echo error_info_msg('No se ha podido cargar el archivo .xlsx');
        }

        /* Finalizamos la transaccion */
        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
        } else {
            $this->db->trans_commit();
        }
    }

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

    function get_coincidencias($string, $list, $num_archivo, $subject = '') {
//        print_r($list);
        $encontrado = false;
        echo tagcontent('script', '$("#p_subject").text("' . $subject . '")');

        foreach ($list as $value) {
            echo tagcontent('script', '$("#p_id").text("' . $value->id . '")');
            if (substr_compare($string, $value->nombre, 0, strlen($string), true) == 0) {
                $encontrado = true;
                break;
            }
        }

        if ($encontrado) {
            return $value->id;
        } else {
            echo error_info_msg('El string "' . $string . '" de ' . $subject . ' no se encuentra registrado en el sistema, o el nombre no coincide');

            $this->db->trans_rollback();
            die();
            /* FUNCION PARA GUARDAR INCIDENTES QUE NO SE PUDIERON GRABAR */
//            $this->save_incidentes($string, $subject, $num_archivo);
            //return '-1';
        }
    }

    function get_nacionalidadId($string, $list, $subject = '') {
//        print_r($list);
//        die();
        $string = substr($string, 0, 3);
        $encontrado = false;
        echo tagcontent('script', '$("#p_subject").text("' . $subject . '")');

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

    function save_incidentes($string, $subject, $num_archivo_paciente) {
        $data = array(
            'string' => $string,
            'id_paciente' => $num_archivo_paciente,
            'campo' => $subject,
        );
        $this->generic_model->save('incidentes_importacion', $data);
    }

}
