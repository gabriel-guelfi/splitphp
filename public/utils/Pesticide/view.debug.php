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
?>

<div id="debug-contents">
    <h2 style="text-align: center;"><?php echo $pageTitle; ?></h2>
    <!--Header with general debug data-->
    <b>URI: </b><?php echo $_SERVER['REQUEST_URI']; ?>
    <br>
    <br>
    <b>Debug called from: </b><?php echo isset($backtrace[1]['class']) ? $backtrace[1]['class'] : ""; ?><?php echo isset($backtrace[1]['type']) ? $backtrace[1]['type'] : ""; ?><?php echo $backtrace[1]['function']; ?>()
    in 
    "<?php echo $backtrace[0]['file']; ?>", line <?php echo $backtrace[0]['line']; ?>.
    <br>
    <br>
    <b>Date: </b><?php echo $time; ?>
    <br>
    <br>
    <hr>
    <h5>ARGUMENTS PASSED ON CALLER FUNCTION "<?php echo isset($backtrace[1]['class']) ? $backtrace[1]['class'] : ""; ?><?php echo isset($backtrace[1]['type']) ? $backtrace[1]['type'] : ""; ?><?php echo $backtrace[1]['function']; ?>()": </h5>
    <?php
    if (!empty($backtrace[1]['args'][0])):
        ?>
        <div class="blank-screen">
            <?php $this->dump($backtrace[1]['args'][0], "Args"); ?>
        </div>
    <?php else: ?>
        <div style="border: 1px solid;">
            <p>--- No arguments passed on this function ---</p>
        </div>
    <?php endif; ?>
    <br>
    <hr>
    <!--Printing the current session data-->
    <h5>CURRENT SESSION: </h5>
    <?php if (!empty($_SESSION)): ?>
        <div class="blank-screen">
            <?php $this->dump($_SESSION, "Session"); ?>
        </div>
        <?php
    else:
        ?>
        <div style="border: 1px solid;">
            <p>--- Session is currently empty ---</p>
        </div>
    <?php endif; ?>
    <br>
    <hr>
    <!--Printing the current request data-->
    <h5>CURRENT REQUEST: </h5>
    <?php if (!empty($request)): ?>
        <div class="blank-screen">
            <?php $this->dump($request); ?>
        </div>
    <?php else:
        ?>
        <div style="border: 1px solid;">
            <p>--- Request is currently empty ---</p>
        </div>
    <?php endif; ?>
    <br>
    <hr>
    <!--Printing custom debug messages-->
    <h5>CUSTOM DEBUG MESSAGES:</h5>
    <?php if (!empty($messages)): ?>
        <div class="blank-screen">
            <?php
            foreach ($messages as $k => $m):
                $this->dump($m, $k);
                ?>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div style="border: 1px solid;">
            <p>--- No custom debug messages ---</p>
        </div>
    <?php endif; ?>
    <br>
    <hr>
    <!--Printing custom debug data-->
    <h5>CUSTOM PRINTABLE DATA:</h5>
    <?php if (!empty($print_data)): ?>
        <div class="blank-screen">
            <?php
            foreach ($print_data as $k => $p):
                ?>
                <p><?php $this->dump($p, $k); ?></p>
            <?php endforeach; ?>
        </div>
        <?php
    else:
        ?>
        <div style="border: 1px solid;">
            <p>--- No custom data to print ---</p>
        </div>
    <?php endif; ?>
    <br>
    <hr>
    <!--Tracing execution-->
    <h5>EXECUTION HISTORY:</h5>
    <div class="blank-screen">
        It starts with:
        <?php
        $count = 1;
        for ($i = (count($backtrace) - 1); $i >= 1; $i--):
            $method = false;
            if (array_key_exists("class", $backtrace[$i])) {
                $method = true;
            }
            ?>
            <p class="history-item">
                <?php echo $count; ?> - 
                <?php echo ($method ? "Method <b>" . $backtrace[$i]['class'] . $backtrace[$i]['type'] . $backtrace[$i]['function'] : "Function <b>" . $backtrace[$i]['function']); ?>()</b> 
                <?php echo!empty($backtrace[$i]['file']) ? 'called from file"' . $backtrace[$i]['file'] . '" on line ' . $backtrace[$i]['line'] : ''; ?>.
                <br>
                <br>
                <span style="font-size:12px;">The following arguments were passed on this <?php echo $method ? 'method' : 'function'; ?>:</span>
                <br>
                <?php $this->dump($backtrace[$i]['args'], "Args"); ?>
                <b>&#8675;</b>
            </p>
            <?php
            $count++;
        endfor;
        ?>
        <p class="history-item">Then, the script stopped running.</p>
    </div>

    <div id="pesticide-footer">Powered By: Pesticide PHP Debugger Tool</div>
</div>