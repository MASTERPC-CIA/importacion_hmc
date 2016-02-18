<!--<div id="div_titulo_header" class="col-md-12" style="text-align: center;">
    <h3>Bancos</h3>
    <input id="titulo_header" type="hidden" value="Bancos">
</div>  -->
<!-- Left side column. contains the logo and sidebar -->
      <aside class="main-sidebar">
        <!-- sidebar: style can be found in sidebar.less -->
        <section class="sidebar">
          <!-- Sidebar user panel -->
          <div class="user-panel">
            <?php
//                echo $this->load->view('login/user_logo','',TRUE);
            ?>
          </div>
          <!-- search form -->
        <form action="#" method="get" class="sidebar-form">
            <div class="input-group">
                <input type="text" name="q" class="form-control" placeholder="Search..."/>
                <span class="input-group-btn">
                    <button type='submit' name='search' id='search-btn' class="btn btn-flat"><i class="fa fa-search"></i></button>
                </span>
            </div>
        </form>
        <!-- /.search form -->
          <!-- sidebar menu: : style can be found in sidebar.less -->
        <div class="sidebar-nav navbar-collapse">
          <ul class="sidebar-menu">
            <!--<li class="header" id="client_name">CLIENTE:</li>-->
          <?php
            if(!empty($slidebar_actions)){
                echo $slidebar_actions;
            }
          ?>
            <li class="header">PACIENTES</li>
            <li class="active treeview">
                <a href="#"><i class="fa fa-dashboard"></i><span> Pacientes</span><i class="fa fa-angle-left pull-right"></i></a>
                <ul class="treeview-menu">
                    <li>
                        <a href="<?= base_url('importacion_hmc/pacientes')?>"><i class="glyphicon glyphicon-plus"></i> pacientes</a>
                    </li>


                </ul>
                <!-- /.nav-second-level -->
            </li>                        
                        
          </ul>
          </div>

        </section>
        
        <section id="client_area">
            
        </section>
        <!-- /.sidebar -->
      </aside>
