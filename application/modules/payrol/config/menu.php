<?php

// module name
$HmvcMenu["payrol"] = array(
    //set icon
    "icon"           => "<i class='fa fa-cubes'></i>", 
    
    //menu name
    "email" => array( 
        "controller" => "single",
        "method"     => "index",
        "permission" => "create"
    ), 
 
    //group level name
    "home" => array(
        //menu name
        'new_message'    => array( 
            "controller" => "home",
            "method"     => "create",
            "permission" => "create"
        ),
        //menu name
        'inbox_message'  => array( 
            "controller" => "home",
            "method"     => "index",
            "permission" => "read"
        ), 
    ), 
 
    //single link name
    "password" => array( 
        "controller" => "single",
        "method"     => "index",
        "permission" => "read"
    ),  

    //group level name
    "save_successfully" => array(
        //menu name
        'dashboard'      => array( 
            "controller" => "test",
            "method"     => "create",
            "permission" => "create"
        ),
        //menu name
        'message'   => array( 
            "controller" => "test",
            "method"     => "index",
            "permission" => "read"
        ), 
    ),  
);
   

 