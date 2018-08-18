<?php
$allrules = array();
$allrules = get_option("xa_dp_rules", array());
if (isset($allrules['cart_rules'])) {
    $allrules = $allrules['cart_rules'];
    $settings = get_option('xa_dynamic_pricing_setting',array());
    $rules_per_page = isset($settings['rules_per_page'])?$settings['rules_per_page']:20;
    if(isset($_REQUEST['page_no']))
    {
        $current_page_no = $_REQUEST['page_no'];
    }
    else
    {
        $current_page_no = 1;
    }
    ?>
    <div style="float: right;padding: 10px;">
        <?php
        $pages = floor(count($allrules)/$rules_per_page);
        $pages = count($allrules)%$rules_per_page !=0? $pages+1: $pages;
        if($current_page_no>$pages)
            $current_page_no = 1;
        ?>
        Page: 
        <select style="display: inline-block;" onchange="page_dropdown_func('cart_rules');" id="page_dropdown">
            <?php
            for($i=1;$i<=$pages;$i++)
            {
                $selected= '';
                if($current_page_no==$i)
                {
                    $selected = 'selected';
                }
                echo "<option $selected value='$i'>$i</option>";
            }
            $nextdisable = '';
            $classnext = 'nextbtn';
            if($current_page_no >= $pages)
            {
                $nextdisable = 'disabled';
                $classnext = 'nextbtndisable';
            }
            $prevdisable = '';
            $classprev = 'prevbtn';
            if($current_page_no <= 1)
            {    
                $prevdisable = 'disabled';
                $classprev = 'prevbtndisable';
            }
            ?>
        </select>
        <button type="button" <?php echo $prevdisable;?> class='<?php echo $classprev;?>' onclick="new_page(<?php echo $current_page_no-1;?>,'cart_rules');"></button>
        <button type="button" <?php echo $nextdisable;?> class='<?php echo $classnext;?>' onclick="new_page(<?php echo $current_page_no+1;?>,'cart_rules');"></button>
    </div>
<table class="display_all_rules table widefat" style=" border-collapse: collapse;width:100%;">
    <thead style="font-size: smaller;background-color: lightgrey;">
        <tr style=" border-bottom-style: solid; border-bottom-width: thin;">
            <th class="xa-table-header icon-move" style="font-size: 10px;padding:3px;word-wrap: break-word; width: 5px;">Drag</th>
            <th class="xa-table-header" style="padding:3px;width: 60px;"><?php esc_attr_e('Options', 'eh-dynamic-pricing-discounts'); ?></th>
            <th class="xa-table-header" style="word-wrap: break-word; width: 10px;"><?php esc_attr_e('Rule no.', 'eh-dynamic-pricing-discounts'); ?></th>
            <th class="xa-table-header" style=""><?php esc_attr_e('Offer Name', 'eh-dynamic-pricing-discounts'); ?></th>
            <th class="xa-table-header" style=""><?php esc_attr_e('Check on', 'eh-dynamic-pricing-discounts'); ?></th>
            <th class="xa-table-header" style=""><?php esc_attr_e('Min', 'eh-dynamic-pricing-discounts'); ?></th>
            <th class="xa-table-header" style=""><?php esc_attr_e('Max', 'eh-dynamic-pricing-discounts'); ?></th>
            <th style="word-wrap:break-word;width:5px;" class="xa-table-header" ><?php esc_attr_e('Discount Type', 'eh-dynamic-pricing-discounts'); ?></th>
            <th class="xa-table-header" style=""><?php esc_attr_e('Value', 'eh-dynamic-pricing-discounts'); ?></th>
            <th style="word-wrap: break-word;width: 10px;" class="xa-table-header" ><?php esc_attr_e('Max Discount', 'eh-dynamic-pricing-discounts'); ?></th>
            <th class="xa-table-header" style=""><?php esc_attr_e('Allowed Roles', 'eh-dynamic-pricing-discounts'); ?></th>
            <th class="xa-table-header" style=""><?php esc_attr_e('From Date', 'eh-dynamic-pricing-discounts'); ?></th>
            <th class="xa-table-header" style=""><?php esc_attr_e('To Date', 'eh-dynamic-pricing-discounts'); ?></th>
            <th class="xa-table-header" style="padding-right: 4px;padding-left: 4px;"><?php esc_attr_e('Adjustment', 'eh-dynamic-pricing-discounts'); ?></th>
            <th class="xa-table-header" style="padding-right: 4px;padding-left: 4px;"><?php esc_attr_e('For Emails', 'eh-dynamic-pricing-discounts'); ?></th>
            <th class="xa-table-header" style="padding-right: 4px;padding-left: 4px;"><?php esc_attr_e('Min Order Count', 'eh-dynamic-pricing-discounts'); ?></th>
            <th class="xa-table-header" style="padding-right: 4px;padding-left: 4px;"><?php esc_attr_e('Min Order Amt', 'eh-dynamic-pricing-discounts'); ?></th>
            <th class="xa-table-header" style="padding-right: 4px;padding-left: 4px;"><?php esc_attr_e('Attributes', 'eh-dynamic-pricing-discounts'); ?></th>
            <th class="xa-table-header" style="padding-right: 4px;padding-left: 4px;width: 10px;"><?php esc_attr_e('Payment Gateways', 'eh-dynamic-pricing-discounts'); ?></th>
            <th class="xa-table-header" style="padding-right: 4px;padding-left: 4px;width: 10px;"><?php esc_attr_e('Shipping Methods', 'eh-dynamic-pricing-discounts'); ?></th>
            <th class="xa-table-header" style="padding-right: 4px;padding-left: 4px;width: 10px;"><?php esc_attr_e('Min Stock', 'eh-dynamic-pricing-discounts'); ?></th>
            <th class="xa-table-header" style="padding-right: 4px;padding-left: 4px;width: 10px;"><?php esc_attr_e('Max Stock', 'eh-dynamic-pricing-discounts'); ?></th>
            
        </tr>
    </thead>
    <tbody style="font-size: smaller;">
        <?php
            $customclass = 'saved_row';
            if(empty($allrules))
            {
                echo '<tr class="' . $customclass . ' " style="border-bottom:lightgrey; border-bottom-style: solid; border-bottom-width: thin;">';
                echo '<td colspan=20> '.__('There are no rules created to create a rule click the "Add New Rule" button on top left-hand side.','eh-dynamic-pricing-discounts').'</td>';             
            }
            $begin = ($current_page_no-1) * $rules_per_page;
            $begin++;
            $end = $current_page_no * $rules_per_page;
            $newrules=array();
            for($i=$begin; $i<=$end; $i++)
            {   
                if(isset($allrules[$i]))
                    $newrules[$i] = $allrules[$i];
            }
            $allrules = $newrules;       
            foreach ($allrules as $key => $value) {
                echo '<tr class= "saved_row " style="border-bottom: lightgrey; border-bottom-style: solid; border-bottom-width: thin;"><td class="icon-move " style="width:10px;cursor: move"></td>';
                echo '<td style="margin-left: 0px; margin-right: 0px; padding-left: 0px; padding-right: 0px;">';
                echo '<button class="editbtn"   type="submit" name="edit" value="' . $key . '" ></button>';
                echo '<button class="deletebtn" type="submit" name="delete" value="' . $key . '" ></button>';
                echo "</td>";

                echo '<td>' . $key . '</td>';
                if (!isset($value['adjustment'])) {
                    $value['adjustment'] = null;
                }
                foreach ($value as $key2 => $value2) {
                    if($key2=='attributes_mode')
                    {
                        continue;
                    }
                    if ($key2 == 'offer_name') {
                        echo "<td style=\"width:15%;\">";
                        if (!empty($value2))
                            echo $value2;
                        else
                            echo '  -  ';
                        echo "</td>";
                    }
                    elseif ($key2 == 'check_on') {
                        echo "<td style=\" \">";
                        $value2 = str_replace('TotalQuantity', 'Total units', $value2);
                        $value2 = str_replace('Quantity', 'No. of Items', $value2);

                        if (!empty($value2))
                            esc_attr_e($value2, 'eh-dynamic-pricing-discounts');
                        else
                            echo '  -  ';
                        echo "</td>";
                    }
                    
                    elseif ($key2 == 'attributes') {
                        echo "<td style=\" \">";
                        if(!empty($value2) && !empty($value2['at_taxonomy']) && !empty($value2['at_val'])){
                            foreach($value2['at_taxonomy'] as $key=>$tax_name){
                                $attr_val=!empty($value2['at_val'][$key])?$value2['at_val'][$key]:'';
                                $tax_name= str_replace('pa_', '', $tax_name);
                                echo "$tax_name = $attr_val </br>";
                            }
                        }
                        echo "</td>";
                    }elseif ($key2 == 'allowed_payment_methods' ) {
                        echo '<td >';
                        if(!empty($value2))
                        foreach ($value2 as $val) {
                            if (!empty($val)) {
                                echo  $val . '</br>';
                            }
                        }
                        echo '</td>';
                    }
                    elseif (!empty($value2) && is_array($value2) ) {
                        echo '<td style="width:15%;padding-right: 4px;padding-left: 4px;" class="product_name" id=' . implode(',', $value2) . '>';
                        foreach ($value2 as $val) {
                            if (!empty($val)) {
                                echo '<span class="highlight">' . $val . '</span></br>';
                            }
                        }
                        echo '</td>';
                    }
                    else {
                        echo "<td style=\" \">";
                        if (!empty($value2))
                            esc_attr_e($value2, 'eh-dynamic-pricing-discounts');
                        else
                            echo '  -  ';
                        echo "</td>";
                    }
                }

                echo '</tr>';
            }
        
        ?>
    </tbody>
    <tfoot></tfoot>
</table>


    <?php
}