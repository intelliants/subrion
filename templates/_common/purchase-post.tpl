{if iaTransaction::PASSED == $transaction}
    <div class="alert alert-success">{lang key='thanks'}</div>
{/if}

{ia_hooker name='smartyPostPurchase'}

<h2>{lang key='payment_details'}</h2>
<div class="transaction_info">
    <table class="table table-striped">
    <tbody>
        <tr>
            <td>{lang key='total_paid'}:</td>
            <td>{$order.payment_gross|escape}</td>
        </tr>
        <tr>
            <td>{lang key='currency'}:</td>
            <td>{$order.mc_currency|escape}</td>
        </tr>
        <tr>
            <td>{lang key='payment_date'}:</td>
            <td>{$order.payment_date|escape}</td>
        </tr>
        <tr>
            <td>{lang key='payment_status'}:</td>
            <td>{$order.payment_status|escape}</td>
        </tr>
        <tr>
            <td>{lang key='payer_name'}:</td>
            <td>{$order.first_name|escape} {$order.last_name|escape}</td>
        </tr>
        <tr>
            <td>{lang key='payer_email'}:</td>
            <td>{$order.payer_email|escape}</td>
        </tr>
        <tr>
            <td>{lang key='reference_id'}:</td>
            <td>{$order.txn_id|escape}</td>
        </tr>
    </tbody>
    </table>
</div>

{if !empty($transaction.return_url)}
    <div class="actions">
        <p>{lang key='payment_redirect_message' seconds='<code id="redirect-counter"></code>'}</p>
        <button id="payment-redirect" class="btn btn-primary">{lang key='go'}</button>
    </div>

    {ia_add_js}
$(function()
{
    $('#payment-redirect').on('click', function()
    {
        window.location.href = '{$transaction.return_url}';
    });

    var secondsLeft = 20;
    window.setInterval(function()
    {
        $('#redirect-counter').text(secondsLeft);
        secondsLeft -= 1;

        if (secondsLeft == 0)
        {
            $('#payment-redirect').trigger('click');
        }
    }, 1000);
});
    {/ia_add_js}
{/if}