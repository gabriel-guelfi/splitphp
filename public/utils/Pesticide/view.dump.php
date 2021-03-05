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

<div class="pesticide-container">
    <ul class="pesticide-dump">
        <?php if (!is_array($var) && $vartype !== 'object'): ?>
            <li><b>&#8627;</b> 
                <span class="pesticide-varname">
                    <?php echo '[' . $name . ']'; ?>
                </span> <?php echo '(' . $vartype . ' - length:' . strlen((string) $var) . ')' ?> = 
                <b><?php echo $var; ?></b>
            </li>
        <?php else: $length = count((array) $var); ?>
            <li class="pesticide-dropdown">
                <b>&#8600;</b> 
                <span class="pesticide-varname"><?php echo '[' . $name . ']'; ?></span> 
                <?php echo '(' . $vartype . ' - length:' . $length . ')'; ?></li>
            <li class="pesticide-hidden">
                <ul>
                    <?php if ($vartype == "object"): ?>
                        <li class='pesticide-obs'>
                            *This is an object. It may contain inaccessible(private or protected) properties that will not be shown in this dump list.
                        </li>
                    <?php endif; ?>
                    <?php if (empty($length)): ?>
                        <li class='pesticide-obs'>
                            *This list is empty. No items to dump.
                        </li>
                    <?php endif; ?>
                    <?php foreach ($var as $k => $v): ?>
                        <li>
                            <?php echo $this->dump($v, $k, true); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </li>
        <?php endif; ?>
        <span id="pesticide-footer">Powered By: Pesticide PHP Debugger Tool</span>
    </ul>
</div>
