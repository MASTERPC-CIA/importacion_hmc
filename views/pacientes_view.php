<?php
	echo Open('form', array('id'=>'import_form', 'action'=>base_url('importacion_hmc/pacientes/importar'),'method'=>'post'));
//	echo Open('form', array('id'=>'import_form', 'action'=>base_url('importacion_hmc/pacientes/importar1'),'method'=>'post'));
//		echo tagcontent('h1','DEPOSITOS - Cheques',array('class'=>'titulos'));
        
                echo Open('div', array('class' => 'col-md-6')); 
                
                    echo tagcontent('label', 'Archivo Excel');
                    
//                        echo tagcontent('div', input(array('type' => 'file', 'name' => 'xls_input', 
//                            'class' => 'form-control col-md-3')));

                    echo Open('div', array('class' => 'row')); 
                    // campo de excel funcional 
//                    echo Open('input', array('type'=>'file','name' => 'userfile', 'class' => 'col-md-6')); 
                    echo Open('input', array('type'=>'text','name' => 'string', 'class' => 'col-md-6')); 


                    echo Open('div', array('class' => 'col-md-3')); 
                        echo tagcontent('button','Importar',array( 'id'=>'ajaxformbtn','name'=>'import_btn', 'data-target'=>'new_deposito_out','class'=>'btn btn-primary'));
                    echo Close('div'); 
                    
                    echo  input(array('type' => 'hidden', 'name' => 'action', 
                         'class' => 'form-control', 'value'=>'upload'));

                echo Close('div'); 
	echo Close('form');

	echo tagcontent('div','',array('id'=>'new_deposito_out'));
        echo Open('div', array('id'=>'new_total_out', 'style'=>'display:none;'));
        echo '<h3>Recorriendo </h3>';
        echo '<p>Usuario N_archivo:</p> ';
        echo '<span id="p_user_id"></span>';
        echo '<p id="p_subject"></p> ';
        echo 'ID: <span id="p_id"></span>';
        echo Close('div');
//	echo tagcontent('div','',array('id'=>'new_deposito_out'));


$jsarray = array(
    base_url('application/modules/bancos/js/deposito_cheques.js'),
);
echo jsload($jsarray);
?>
<script>
    $("#import_form").submit(function(){
        $('#new_total_out').show();
    });
</script>
    
