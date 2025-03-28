<?php
/**
 * COmanage Registry Attribute Enumeration JavaScript
 * Applies jQuery widgets and flash messages
 *
 * Portions licensed to the University Corporation for Advanced Internet
 * Development, Inc. ("UCAID") under one or more contributor license agreements.
 * See the NOTICE file distributed with this work for additional information
 * regarding copyright ownership.
 *
 * UCAID licenses this file to you under the Apache License, Version 2.0
 * (the "License"); you may not use this file except in compliance with the
 * License. You may obtain a copy of the License at:
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * 
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v4.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

// eg: "CoPersonRole"
$mname = Inflector::singularize($this->name);
// eg: "co_person_roles"
$tname = Inflector::tableize($this->name);

// Views that include this element 
// 
// (1) should be sure to call enum_update_gadgets(false) onload, eg:
// 
// function js_local_onload() {
//   enum_update_gadgets(false);
// }
// 
// (2) define the array of enumerable fields (in PHP, using underscore names)
// 
//   $args = array(
//     'enumerables' => array('o', 'ou', 'title')
//   );
//   print $this->element('enumerations', $args);
//
// or if the field supports multiple types
//
//   $args = array(
//     'enumerables' => array('issuing_authority'),
//     'typeFields' => array('issuing_authority' => 'document_type')
//   );
//   print $this->element('enumerations', $args);
// 
// (3) use enumerableField to render the field
// 
//   $args = array(
//     'column' => 'o',
//     'editable' => true
//   );
//   print $this->element('enumerableField', $args);
//
//  and maybe also define "modelName" and "fieldName" if doing something weird
//  (like in a CoPetition)
//
// (4) in the controller, set $vv_enums with the available enumerations, keyed
//     on Model.attribute.[type]
?>
<script type="text/javascript">
  // Enums as constructed from the Attribute Enumeration Dictionary
  var enums = [];
  
  // We also need to track if the Dictionary is coded, so we use an object or
  // array appropriately.
  var coded = [];
  
  // And whether or not other values are permitted
  var other = [];

  // Holding the default values
  var p = {};

  <?php
    // Build javascript arrays based on our PHP configuration
    foreach($vv_enums as $attr => $cfg) {
      $attrBits = explode('.', $attr, 3);
      
      $cfg = $vv_enums[$attr];
      
      // The current persisted value in the database (if any)
      $curval = '';

      if(!empty($$tname[0][ $attrBits[0] ][ $attrBits[1] ])) {
        // Regular model
        $curval = $$tname[0][ $attrBits[0] ][ $attrBits[1] ];
      } elseif(!empty($co_enrollment_attributes)) {
        // Petition
        foreach($co_enrollment_attributes as $ea) {
          if(isset($ea['model']) 
             && $ea['model'] == $attrBits[0]
             && $ea['field'] == $attrBits[1]) {
            $curval = $ea['default'] ?? '';
          }
        }
      }

      // We are passing the defaultValue to a tmp variable
      print "p['" . $attrBits[0] . Inflector::camelize($attrBits[1]) . "'] = \""
            . $curval
            . "\";\n";
      
      print "other['" . $attr . "'] = " . ($cfg['allow_other'] ? "true" : "false") . ";\n";
      
      if($cfg['coded']) {
        print "coded['" . $attr . "'] = true\n";
        print "enums['" . $attr . "'] = {\n";
        
        if(!empty($cfg['dictionary'])) {
          foreach($cfg['dictionary'] as $code => $label) {
            // might be safer not to emit a trailing comma, but it should work with modern javascript
            print addslashes($code) . ": '" . addslashes($label) . "',\n";
          }
        }
        
        print "};\n";
      } else {
        print "coded['" . $attr . "'] = false\n";
        print "enums['" . $attr . "'] = [\n";
        
        if(!empty($cfg['dictionary'])) {
          foreach($cfg['dictionary'] as $code => $label) {
            print "'" . addslashes($label) . "',\n";
          }
        }
        
        print "];\n";
      }
    }
  ?>
  
  // Set the value of the text field (which is what we'll save in the database)
  // based on the updated attribute enumeration field. Note this value is subject
  // to server side validation.
  function enum_set_value(widget, eid, column) {
    // We don't want to make any changes when we get an Other notification
    // if the entry is blank
    // if(widget == 'Other') {
    //   if(document.getElementById(eid+widget).value == '') {
    //     return;
    //   }
    // }

    document.getElementById(eid).value = document.getElementById(eid+widget).value;

    // Blank out the inactive widget
    if(widget == 'Select') {
      document.getElementById(eid+'Other').value = '';
    } else {
      document.getElementById(eid+'Select').value = '';
    }
  }
  
  // Update gadgets according to the current state.
  function enum_update_gadgets(resetField) {
    // The attributes which support enumeration
    var attributes = [
      <?php
        foreach($enumerables as $attr) {
          print "\"" . $attr . "\",\n";
        }
      ?>
    ];
    
    // The enumerable attributes which have associated type fields
    var typeFields = [];
    
    <?php
      if(!empty($typeFields)) {
        foreach($typeFields as $attr => $field) {
          print "typeFields['" . $attr . "'] = \"" . $field . "\";\n";
        }
      }
    ?>
    
    <?php foreach($enumerables as $attr): ?>
      <?php $bits = explode(".", $attr, 2); ?>
      
      var curattr = "<?php print $attr; ?>";
      var attrid = "<?php print $bits[0] . Inflector::camelize($bits[1]); ?>";
      var typeid = null;
      
      if(typeFields[curattr]) {
        // Append the current type (if set) to curattr
        typeid = "<?php if(!empty($typeFields)) { print $mname . Inflector::camelize($typeFields[$attr]); } ?>";
        curattr += "." + document.getElementById(typeid).value;
      }
      
      if(resetField) {
        // Blank out the value to avoid any issues when swapping Document Types
        document.getElementById(attrid).value = "";
      }
      
      if(enums[curattr] && Object.keys(enums[curattr]).length > 0) {
        // We have a dictionary, so show the select
        $("#<?php print $bits[1]; ?>-enumeration").show("fade");
        
        // Show the free form field if appropriately configured

        if(other[curattr]) {
          $("#<?php print $bits[1]; ?>-other").show("fade");
        } else {
          $("#<?php print $bits[1]; ?>-other").hide("fade");
        }

        // Always hide the actual value field when the dictionary is in use
        $("#<?php print $bits[1]; ?>-field").hide("fade");
        
        const select = document.getElementById(attrid + 'Select');

        if(select !== undefined && select !== null) {
          if(select.options.length > 0) {
            select.options.length = 0;
          }

          let found = 0; // Did we find the current value in the select list?
          let i = 0;

          // Add an empty option
          select.options[i++] = new Option('', '', false, false);

          if(coded[curattr]) {
            // sort the fields
            const sortedEnumCurAttr = sortProperties(enums[curattr])
            // We want the code as the select value
            for(let j in sortedEnumCurAttr) {
              select.options[i++] = new Option(
                sortedEnumCurAttr[j][1],
                sortedEnumCurAttr[j][0],
                false,
                p[attrid] === sortedEnumCurAttr[j][0]
              );

              if(p[attrid] === sortedEnumCurAttr[j][0]) {
                found++;
              }
            }
          } else {
            // No code, so use the entry as the value
            // todo: when do i get in here??
            for(let j in enums[curattr]) {
              select.options[i++] = new Option(
                enums[curattr][j],
                enums[curattr][j],
                false,
                p[attrid] == j.toString()
              );

              if(p[attrid] == j.toString()) {
                found++;
              }
            }
          }

          if(found == 0) {
            // Set the default value in the other field
            document.getElementById(attrid + 'Other').value = p[attrid];
          }
        }
      } else {
        // Standard form element, hide the enumeration widgets
        $("#<?php print $bits[1]; ?>-enumeration").hide("fade");
        $("#<?php print $bits[1]; ?>-other").hide("fade");
        $("#<?php print $bits[1]; ?>-field").show("fade");
      }
    <?php endforeach; // $enumerables ?>
  }
</script>
