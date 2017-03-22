<fieldset class="collapsible" id="paymentgateways" {if $smarty.post.inputpaymentgateway}style="display: none;"{/if}>
    <legend>{lang key='payment_gateway'}</legend>
    {foreach $gateways as $paymentgateway}
        <div style="margin:5px; float:left;">
            <a href="javascript: void(0);" id="{$paymentgateway.name}">
                <img src="plugins{$smarty.const.IA_DS}{$paymentgateway.plugin}{$smarty.const.IA_DS}img{$smarty.const.IA_DS}{$paymentgateway.name}.gif" alt="{$paymentgateway.gateway}">
            </a>
        </div>
    {/foreach}
</fieldset>

<fieldset id="paymentgatewayform" style="display: none;">
    <legend>{lang key='back_to_gateway_list'}</legend>
</fieldset>

{ia_add_js}
var active_gateway = '{$smarty.post.inputpaymentgateway}';
$(function()
{
    var legend = $('#paymentgatewayform > legend').html();

    if (active_gateway)
    {
        element_id = active_gateway;
        $("#paymentgateways").fadeOut();
        temp = "<div>" + legend.replace("{current_gateway}", $("#" + element_id + "> img").attr("alt")) + "</div>";
        $("#paymentgatewayform").empty().html(temp).fadeIn();
        $("#" + element_id).clone().appendTo("#paymentgatewayform").attr("id", "copy_" + element_id).after('<input type="hidden" name="inputpaymentgateway" value="' + element_id + '">');
    }

    $('#paymentgateways > div > div > a').click(function()
    {
        element_id = $(this).attr("id");
        $("#paymentgateways").fadeOut();
        temp = "<div>" + legend.replace("{current_gateway}", $("#" + element_id + "> img").attr("alt")) + "</div>";
        $("#paymentgatewayform").empty().html(temp).fadeIn();
        $("#"+element_id).clone().appendTo("#paymentgatewayform").attr("id", "copy_" + element_id).after('<input type="hidden" name="inputpaymentgateway" value="' + element_id + '">');
    });
});

function backToPaymentGatewayList()
{
    $("#paymentgateways").fadeIn();
    $("#paymentgatewayform").empty().fadeOut();
}
{/ia_add_js}