<table border="0" width="100%">
    <tr>
        <td>
            <h1>{$core.config.site}</h1>
        </td>
        <td align="right">
            Company address<br>
            Town<br>
            County<br>
            Country
        </td>
    </tr>
    <tr>
        <td colspan="2"><hr></td>
    </tr>
    <tr>
        <td>
            <h2>Invoice {$invoice.id}</h2>
        </td>
        <td>
            <p><strong>Transaction:</strong> {$invoice.transaction_id}</p>
            <p><strong>PO Number:</strong> 0123456789</p>
        </td>
    </tr>
    <tr>
        <td colspan="2" valign="top">
            <p>{$invoice.fullname|escape}</p>
            <p>
                {$invoice.address1|escape}<br>
                {if $invoice.address2}{$invoice.address2|escape}<br>{/if}
                {if $invoice.zip}{$invoice.zip|escape}<br>{/if}
                {if $invoice.country}{$invoice.country|escape}{/if}
            </p>
        </td>
    </tr>
    <tr>
        <td><strong>Invoice issued on {$invoice.date_created|date_format:$core.config.date_format}</strong></td>
        <td><strong>Payment due by {$invoice.date_due|date_format:$core.config.date_format}</strong></td>
    </tr>
    {if $items}
    <tr>
        <td colspan="2">
            <table border="1" cellspacing="1" cellpadding="10" width="100%">
                <tr>
                    <th>#</th>
                    <th>Description</th>
                    <th>Price</th>
                    <th>Qty</th>
                    <th>Net</th>
                    <th>VAT %</th>
                    <th>VAT</th>
                    <th>Gross</th>
                </tr>
                {$amount = 0}
                {foreach $items as $entry}
                    <tr>
                        <td>{$entry@iteration}</td>
                        <td>{$entry.title|escape}</td>
                        <td>{$entry.price}</td>
                        <td>{$entry.quantity}</td>
                        <td>
                            {$subtotal = $entry.price * $entry.quantity}
                            {$subtotal|number_format:2}
                        </td>
                        <td>{$entry.tax}</td>
                        <td>
                            {$tax = $subtotal / 100 * $entry.tax}
                            {$tax|number_format:2}
                        </td>
                        <td>
                            {$total = $subtotal + $tax}
                            {$amount = $amount + $total}
                            <strong>{$total|number_format:2}</strong>
                        </td>
                    </tr>
                {/foreach}
                <tr>
                    <td colspan="7" align="right"><strong>Total:</strong></td>
                    <td><strong>{$amount|number_format:2}</strong></td>
                </tr>
            </table>
        </td>
    </tr>
    {/if}
    <tr>
        <td>For more information, please contact {$core.config.site_email}.</td>
    </tr>
</table>