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

jQuery(document).ready(function () {
    jQuery('.pesticide-dropdown').click(function () {
        var inner = jQuery(this).parent().find('>li.pesticide-hidden,>li.pesticide-shown');
        if (inner.hasClass('pesticide-hidden')) {
            inner.show('fast');
            inner.removeClass('pesticide-hidden');
            inner.addClass('pesticide-shown');
        } else if(inner.hasClass('pesticide-shown')){
            inner.hide('fast');
            inner.removeClass('pesticide-shown');
            inner.addClass('pesticide-hidden');
            
        }
    });
});

