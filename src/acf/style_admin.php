<?php

namespace videoconstructor\acf;

class style_admin
{


    public function __construct(){
        add_action( 'acf/input/admin_head', [$this, 'my_acf_admin_head'], 20 );
    }



    public function my_acf_admin_head() {
        ?>
        <link href="https://cdn.jsdelivr.net/npm/@mdi/font@7.4.47/css/materialdesignicons.min.css" rel="stylesheet">

        <style type="text/css"> 
            <?php include VIDEOCONSTRUCTOR_PATH.'/src/css/admin.css' ; ?>
        </style>
        
        <?php
    }



}