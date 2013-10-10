/*
 * Copyright (c) 2013, MasterCard International Incorporated
 * All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without modification, are 
 * permitted provided that the following conditions are met:
 * 
 * Redistributions of source code must retain the above copyright notice, this list of 
 * conditions and the following disclaimer.
 * Redistributions in binary form must reproduce the above copyright notice, this list of 
 * conditions and the following disclaimer in the documentation and/or other materials 
 * provided with the distribution.
 * Neither the name of the MasterCard International Incorporated nor the names of its 
 * contributors may be used to endorse or promote products derived from this software 
 * without specific prior written permission.
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY 
 * EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES 
 * OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT 
 * SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, 
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED
 * TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; 
 * OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER 
 * IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING 
 * IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF 
 * SUCH DAMAGE.
 */

function simplifyResponseHandler(data) {
    jQuery(".error").remove();
    if(data.error) {
        if(data.error.code == "validation") {
            var fieldErrors = data.error.fieldErrors,
                fieldErrorsLength = fieldErrors.length;
            jQuery('#cc-cvc, #cc-number, #cc-exp-month, #cc-exp-year').css('box-shadow', 'none');
            for (var i = 0; i < fieldErrorsLength; i++) {
                if(fieldErrors[i].field == 'card.cvc') {
                    jQuery('#cc-cvc').css('box-shadow', '0px 0px 5px red');
                } else if(fieldErrors[i].field == 'card.number') {
                    jQuery('#cc-number').css('box-shadow', '0px 0px 5px red');
                } else if(fieldErrors[i].field == 'card.expMonth') {
                    jQuery('#cc-exp-month').css('box-shadow', '0px 0px 5px red');
                } else if(fieldErrors[i].field == 'card.expYear') {
                    jQuery('#cc-exp-year').css('box-shadow', '0px 0px 5px red');
                }
            }
        }
        jQuery("#mp_payment_confirm").removeAttr("disabled");
    } else {
        var token = data["id"];
        jQuery("#mp_payment_form").append("<input type='hidden' name='simplifyToken' value='" + token + "' />");
        jQuery("#mp_payment_form").get(0).submit();
    }
}

jQuery(document).ready(function() {
    jQuery("#mp_payment_form").on("submit", function() {
        jQuery("#mp_payment_confirm").attr("disabled", "disabled");
        SimplifyCommerce.generateToken({
            key: simplify.publicKey,
            card: {
                number: jQuery("#cc-number").val(),
                cvc: jQuery("#cc-cvc").val(),
                expMonth: jQuery("#cc-exp-month").val(),
                expYear: jQuery("#cc-exp-year").val()
            }
        }, simplifyResponseHandler);
        return false;
    });
});