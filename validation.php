<?
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
$missing_fields = '';
function check($field_name, &$missing_fields)
{
  if (!isset($_POST[$field_name]) || ($_POST[$field_name] == ''))
    {
      if ($missing_fields !== '') {
	$missing_fields .= ','.$field_name;
      } else {
	$missing_fields = $field_name;
      }
    }
  else
    {
      #perhaps do some checking ?
      return $_POST[$field_name];
    }
}

function show_validation_errors($class='form_errors', $method='get', $fieldname='missing_fields')
{
  if ($method=='post') {$method = $_POST;} else {$method = $_GET;}
  if (isset($method[$fieldname])) {
    ?><p class="<?=$class;?>">Please enter your <?
    $cnt = 0;
    foreach (explode(',',$method[$fieldname]) as $field) {
      ?><strong><?=str_replace('_',' ',$field)?></strong><?
      $cnt++;
      if ($cnt < sizeof(explode(',',$method[$fieldname]))) {
	echo ', ';
      }
    }
    ?></ul></p><?
    return true;
  }
  return false;
  }
?>
