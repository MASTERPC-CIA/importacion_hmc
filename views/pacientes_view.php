<?php
	echo Open('form', array('action'=>base_url('importacion_hmc/pacientes/get_pacientesdata_xls'),'method'=>'post'));
//		echo tagcontent('h1','DEPOSITOS - Cheques',array('class'=>'titulos'));

                echo Open('div', array('class' => 'col-md-6')); 
                
                    echo tagcontent('label', 'Archivo Excel');
                    
//                        echo tagcontent('div', input(array('type' => 'file', 'name' => 'xls_input', 
//                            'class' => 'form-control col-md-3')));

                    echo Open('div', array('class' => 'row')); 
                    echo Open('input', array('type'=>'file','name' => 'userfile', 'class' => 'col-md-6')); 


                    echo Open('div', array('class' => 'col-md-3')); 
                        echo tagcontent('button','Importar',array( 'id'=>'ajaxformbtn','data-target'=>'pacientes_out','class'=>'btn btn-primary'));
                    echo Close('div'); 
                    
                    echo  input(array('type' => 'hidden', 'name' => 'action', 
                         'class' => 'form-control', 'value'=>'upload'));

                echo Close('div'); 
	echo Close('form');

	echo tagcontent('div','',array('id'=>'pacientes_out'));
        echo tagcontent('div','',array('id'=>'new_total_out'));
//	echo tagcontent('div','',array('id'=>'new_deposito_out'));
