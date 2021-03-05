<?php
///////////////////////////////////////////////////////////////////////////////////////////////////////////
//                                                                                                       //
//                                  PESTICIDE - PHP Debugger Tool                                        //
//                                                                                                       //
//          It is a come-in-handy open source tool for debugging purposes. When you call,                //
//          it reunite all the request and execution info, shows it centralized in a friendly            //
//          Graphic Interface screen, then stops the script till that very moment.                       //
//                                                                                                       //
//          Pesticide - PHP Debugger Tool - Copyright (c) 2017 Gabriel Valentoni Guelfi                  //
//                                                                                                       //
//          >>> CONTACT DEVELOPER:                                                                       //
//          >>> Gabriel Guelfi                                                                           //
//          >>> Website: http://gabrielguelfi.com.br                                                     //
//          >>> Email: gabriel.valguelfi@gmail.com                                                       //
//          >>> Skype: gabriel-guelfi                                                                    //
//                                                                                                       //
//                                                                                                       //
//          This file is part of Pesticide - PHP Debugger Tool.                                          //
//                                                                                                       //
//          Pesticide - PHP Debugger Tool is free software: you can redistribute it and/or modify        //
//          it under the terms of the GNU General Public License as published by                         //
//          the Free Software Foundation, either version 3 of the License.                               //
//                                                                                                       //
//          Pesticide - PHP Debugger Tool is distributed in the hope that it will be useful,             //
//          but WITHOUT ANY WARRANTY; without even the implied warranty of                               //
//          MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the                                //
//          GNU General Public License for more details.                                                 //
//                                                                                                       //
//          You should have received a copy of the GNU General Public License                            //
//          along with this copy of Pesticide - PHP Debugger Tool under the name of LICENSE.pdf.         //
//          If not, see <http://www.gnu.org/licenses/>.                                                  //
//                                                                                                       //
//          Using, modifying and/or running this software or any of its contents, implies consent        //
//          to the terms and conditions explicit within the license, mentioned above.                    //
//                                                                                                       //
///////////////////////////////////////////////////////////////////////////////////////////////////////////

class Pesticide {

    private $uri_path;
    private $theme;

    public function __construct($uri_path = "", $theme = 'default') {
        
        @$dir = end(explode("/",__DIR__));

        $this->uri_path = (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] != "off" ? "https" : "http")."://".$_SERVER["SERVER_NAME"].$uri_path."/".$dir."/";
        $this->theme = $theme;
    }

    public function debug($messages = array(), $print_data = array(), $pageTitle = "Pesticide - PHP Debugger Tool") {
        
        $request = $_REQUEST;
        $backtrace = debug_backtrace();
        $route = str_replace(strrchr($_SERVER["REQUEST_URI"], "?"), "", $_SERVER["REQUEST_URI"]);
        $time = date("Y/m/d - H:i:s", time());

        $uri_path = $this->uri_path;
        $theme = $this->theme;
        
        ob_start();
        include_once __DIR__ . '/view.debug.php';
        include_once __DIR__ . '/view.includes.php';
        echo ob_get_clean();

        die;
    }

    public function dump($var, $name = "", $return = false) {
        $uri_path = $this->uri_path;
        $theme = $this->theme;
        
        $vartype = gettype($var);
            
        ob_start();
        include __DIR__.'/view.dump.php';
        include_once __DIR__ . '/view.includes.php';
        $output = ob_get_clean();
        
        if ($return)
            return $output;
        else
            echo $output;
    }

}

?>