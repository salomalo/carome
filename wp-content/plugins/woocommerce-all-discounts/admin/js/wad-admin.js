(function ($) {
    'use strict';

    $(document).ready(function () {
        if (typeof lang_wordpress != "undefined")
            $.datetimepicker.setLocale(lang_wordpress);
        $(".o-date").each(function ()
        {
            var element = $(this);
            element.datetimepicker({
                //format: 'Y-m-d',
                //date: element.val(),
                //timepicker: false,
                //scrollInput:false,
                /*current: element.val(),
                 starts: 1,
                 position: 'r',*/
//                    onBeforeShow: function(){
//                            element.DatePickerSetDate(element.val(), true);
//                    },
                /* onChange: function(formated, dates){
                 element.val(formated);
                 //                            if ($('#closeOnSelect input').attr('checked')) {
                 element.DatePickerHide();
                 //                            }
                 }*/
            });
        });

        function display_proper_rules_tab()
        {
            var rules_type = $('input[type=radio][name="o-discount[rules-type]"]:checked').attr('value');

            if (rules_type == 'intervals') {
                $("#steps_rules").parent().parent().hide();
                $("#intervals_rules").parent().parent().show();

            } else {
                $("#intervals_rules").parent().parent().hide();
                $("#steps_rules").parent().parent().show();

            }
        }

        display_proper_rules_tab();

        $(document).on("change", "input[type=radio][name='o-discount[rules-type]']", function (e)
        {
            display_proper_rules_tab();
        });

        $(".TabbedPanels").each(function ()
        {
            var defaultTab = 0;
            new Spry.Widget.TabbedPanels($(this).attr("id"), {defaultTab: defaultTab});
        });

        $(document).on("click", ".wad-add-rule", function (e)
        {
            var new_rule_index = $(".wad-rules-table tr").length;
            var group_index = $(this).data("group");
            var raw_tpl = $("#wad-rule-tpl").val();
            var tpl1 = raw_tpl.replace(/{rule-group}/g, group_index);
            var tpl2 = tpl1.replace(/{rule-index}/g, new_rule_index);
            $(this).parents(".wad-rules-table").find("tbody").append(tpl2);
        });

        $(document).on("click", ".wad-add-group", function (e)
        {
            var new_rule_index = 0;
            var group_index = $(".wad-rules-table").length;
            var raw_tpl = $("#wad-first-rule-tpl").val();
            var tpl1 = raw_tpl.replace(/{rule-group}/g, group_index);
            var tpl2 = tpl1.replace(/{rule-index}/g, new_rule_index);
            var html = '<table class="wad-rules-table widefat"><tbody>' + tpl2 + '</tbody></table>';
            $(".wad-rules-table-container").append(html);
        });

        $(document).on("click", ".wad-remove-rule", function (e)
        {
            //If this is the last rule in the group, we remove the entire group
            if ($(this).parent().parent().parent().find("tr").length == 1)
                $(this).parent().parent().parent().parent().remove();
            else
                $(this).parent().parent().remove();

        });

        $(document).on("change", ".wad-pricing-group-param", function (e)
        {
            var selected_value = $(this).val();
            var raw_tpl = wad_values_matches[selected_value];
            var group_index = $(this).data("group");
            var new_rule_index = $(this).data("rule");

            var tpl1 = raw_tpl.replace(/{rule-group}/g, group_index);
            var tpl2 = tpl1.replace(/{rule-index}/g, new_rule_index);
            $(this).parent().parent().find("td.value").html(tpl2);


            var raw_tpl_op = wad_operators_matches[selected_value];

            tpl1 = raw_tpl_op.replace(/{rule-group}/g, group_index);
            tpl2 = tpl1.replace(/{rule-index}/g, new_rule_index);
            $(this).parent().parent().find("td.operator").html(tpl2);
        });

//        $(".add-rf-row").click(function ()
//        {
//            var table_body = $(this).siblings("table").find("tbody");
//            var raw_tpl = $(this).siblings(".rf-row-template").val();
//            var new_key_index = table_body.find("tr").length;
//            var tpl1 = raw_tpl.replace(/{index}/g, new_key_index);
//            table_body.append(tpl1);
//        });
//
//        $(document).on("click", ".remove-rf-row", function (e)
//        {
//            $(this).parent().parent().remove();
//        });

        $("#wad-check-query").click(function ()
        {
            var form = $("#post").serializeJSON();//serializeArray();
            $("#wad-evaluate-loading").css("display", "inline-block");
            $("#debug").html("");
            $.post(
                    ajaxurl,
                    {
                        action: "evaluate-wad-query",
                        data: form
                    },
            function (data) {
                $("#wad-evaluate-loading").hide();
                if (is_json(data))
                {
                    var response = JSON.parse(data);
                    $("#debug").html(response.msg);
                }
                else
                    $("#debug").html(data);
            }
            );
        });

        //We make sure the products list is required when it's visible when the page is loaded
        if ($("#products-list").is(':visible'))
            $("#products-list").prop('required', true);
        if ($("#shipping-list").is(':visible'))
            $("#shipping-list").prop('required', true);

        $(document).on("change", ".discount-action", function (e)
        {
            var selected_value = $(this).val();
            if (selected_value == "free-gift")
            {
                $(".percentage-row, .product-action-row, .shipping-action-row").hide();
                $(".free-gift-row").show();
                $("#products-list").prop('required', false);
                $("#shipping-list").prop('required', false);
            }
            else if (selected_value == "percentage-off-pprice" || selected_value == "fixed-amount-off-pprice" || selected_value == "fixed-pprice")//Product based actions
            {
                $(".free-gift-row, .shipping-action-row").hide();
                $(".percentage-row, .product-action-row").show();
                $("#products-list").prop('required', true);
                $("#shipping-list").prop('required', false);
            }
            else if (selected_value == "percentage-off-shipping-fee" || selected_value == "fixed-amount-off-shipping-fee" || selected_value == "fixed-shipping-fee")// Shipping based actions
            {
                $(".free-gift-row, .product-action-row").hide();
                $(".percentage-row, .shipping-action-row").show();
                $(".percentage-row").show();
                $(".wad-taxable").hide();
                $("#products-list").prop('required', false);
                $("#shipping-list").prop('required', true);
            }
            else //Order based actions
            {
                $(".free-gift-row, .product-action-row, .shipping-action-row").hide();
                $(".percentage-row").show();
                $("#products-list").prop('required', false);
                $("#shipping-list").prop('required', false);
            }
        });

        $(".discount-action").trigger("change");

        $(document).on("change", ".o-list-extraction-type", function (e)
        {
            var selected_value = $(this).val();
            if (selected_value == "by-id")
            {
                $(".extract-by-id-row").show();
                $(".extract-by-custom-request-row").hide();
            }
            else
            {
                $(".extract-by-id-row").hide();
                $(".extract-by-custom-request-row").show();
            }
        });

        $(document).on("change", ".wad-taxonomies-selector", function (e) {
            var param = $(this).val();
            $(this).parent().parent().find(".wad-terms-selector").html(wad_tax_query_recap[param]);
        });

        var labels = $('td:eq(2)', '#intervals_rules');
        var labels2 = $('td:eq(1)', '#steps_rules');
        labels.text('Percentage');
        $('input:radio[name="o-discount[type]"]').change(function () {
            if ($(this).is(':checked')) {
                var parenti = $(this).parent().text();
                var parents = $('input:radio[name="o-discount[type]"]:checked').parent().text();
                labels.text(parenti);
                labels2.text(parents);
            }
        });
        labels.text($('input:radio[name="o-discount[type]"]:checked').parent().text());
        var labels2 = $('td:eq(1)', '#steps_rules');
        $('input:radio[name="o-discount[rules-type]"]').change(function () {
            if ($(this).is(':checked')) {
                var parents2 = $('input:radio[name="o-discount[type]"]:checked').parent().text();
                labels2.text(parents2);
            }
        });

        $(document).on("click", "#wad-activate", function () {
            var code = 0;
            request_registration(code);
        });

        function request_registration(code) {
            $("#spinner").css({display: "inline-block"});
            $.post(
                    ajaxurl,
                    {
                        action: "wad_activate_license",
                        code: code
                    },
            function (data) {
                $("#spinner").css({display: "none"});
                switch (data) {
                    case "purchase_code_used_on_different_site":
                        if (confirm("This purchase code has been used for another website. Do you want to use it anyway? (this will deactivate the previous website activated)")) {
                            var code = 1;
                            request_registration(code);
                        } else {
                            $('#license-message').html("<p>Operation cancelled.</p>");
                        }
                        break;
                    case "200":
                        alert("Activation succeded! Your product is now activated");
                        document.location.reload(true);
                        break;
                    default:
                        alert(data);
                        $('#license-message').html("<p>" + data + "</p>");
                        break;
                }
            }).fail(function (xhr, status, error) {
                $("#spinner").css({display: "none"});
                $('#license-message').html("<p>Activation failed due to a network error. Please, retry later. </p>");
                alert("Activation failed due to a network error. Please, retry later.");
            });
        }

        
        /*
         * 
         * Newsletter
         */
        $(document).on("click", "#wad-dismiss", function () {
            $.post(
                    ajaxurl,
                    {
                        action: "wad_hide_notice",
                    },
                    function (data) {
                        if(data === "ok"){
                           $('#subscription-notice').hide(); 
                        }
                    })
                    .fail(function (xhr, status, error) {
                        alert(error);
    });

        });

        $(document).on("click", "#wad-subscribe", function () {
            $("#wad-subscribe-loader").show();
            $("#wad-subscribe").attr("disabled", true);
            $("#wad-subscribe").addClass('disabled');
            if (!$('#o_user_email').val()) {
                alert('No email found. Please add an email address to subscribe.');
                $("#wad-subscribe-loader").hide();
                $("#wad-subscribe").attr("disabled", false);
                $("#wad-subscribe").removeClass('disabled');
            } else {
                var email = $('#o_user_email').val();
                $.post(
                        ajaxurl,
                        {
                            action: "wad_subscribe",
                            email: email
                        },
                        function (data) {
                            if (data == "true") {
                                $("#wad-subscribe-loader").hide();
                                $('#subscription-notice').hide();
                                $('#subscription-success-notice').show();
                            } else {
                                $("#wad-subscribe-loader").hide();
                                $("#wad-subscribe").attr("disabled", false);
                                $("#wad-subscribe").removeClass('disabled');
                                alert(data);
                            }
                        })
                        .fail(function (xhr, status, error) {
                            $("#wad-subscribe-loader").hide();
                            $("#wad-subscribe").attr("disabled", false);
                            $("#wad-subscribe").removeClass('disabled');
                            alert(error);
                        });
            }

        });


    });

})(jQuery);

function is_json(data)
{
    if (/^[\],:{}\s]*$/.test(data.replace(/\\["\\\/bfnrtu]/g, '@').
            replace(/"[^"\\\n\r]*"|true|false|null|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?/g, ']').
            replace(/(?:^|:|,)(?:\s*\[)+/g, '')))
        return true;
    else
        return false;
}