/*
 * Copyright (c) 2006, Gavin van Lelyveld
 * All rights reserved.
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of the organisation nor the
 *       names of its contributors may be used to endorse or promote products
 *       derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS ``AS IS'' AND ANY
 * EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE REGENTS OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */
function check_email(test_address)
{
    var l, df, af, ret, ac;ret=false;df=0;af=0;ac=0;l=test_address.length;
    if (test_address.search('^[\\w-_\.]*[\\w-_\.]\@[\\w]\.+[\\w]+[\\w]$') == -1){return false;}
    do {if ((l < test_address.length)&&(l>=1)&&(test_address.substr(l,1)==".")) {df=l;}if ((l < test_address.length)&&(l>=1)&&(test_address.substr(l,1)=="@")&&(df > l+1)) {ret=true;af=l;ac=ac+1;}l=l-1;} while (l >= 0);
    if (ret==false){return false;}if (ac > 1) {return false;}if (test_address.substr(test_address.length,1)=="."){return false;}
    return ret;
}
function find(element_id) { return document.getElementById(element_id); }

function validate_text(field_name, message) {
    field_to_check = find(field_name);
    if (field_to_check) {
        if (field_to_check.value == "") {
            alert(message); field_to_check.focus();
						return false;
        }
    } else {
        alert(field_name + " not found");
				return false;
    }
    return true;
}
function validate_email(field_name, message) {
    field_to_check = find(field_name);
    if (field_to_check) {
        if (field_to_check.value == "" || !check_email(field_to_check.value)) {
            alert(message);
						field_to_check.focus();
						return false;
        }
    }
    else
    {
        alert(field_name + " not found");return false;
    }
    return true;
}
