{extends file='customer/page.tpl'}

{block name='page_title'}
    {l s='Mes locations' mod='rentalroute'}
{/block}

{block name='page_content'}
    {if $my_rentals}
        <table class="table table-bordered">
            <thead class="thead-default">
            <tr>
                <th>{l s='Produit' mod='rentalroute'}</th>
                <th>{l s='Quantité' mod='rentalroute'}</th>
                <th>{l s='Date de début' mod='rentalroute'}</th>
                <th>{l s='Date de fin' mod='rentalroute'}</th>
                <th>{l s='Statut' mod='rentalroute'}</th>
            </tr>
            </thead>
            <tbody>
            {foreach from=$my_rentals item=rental}
                <tr>
                    <td>{$rental.product_name}</td>
                    <td>{$rental.quantity}</td>
                    <td>{dateFormat date=$rental.date_start full=0}</td>
                    <td>{dateFormat date=$rental.date_end full=0}</td>
                    <td>{$rental.status}</td>
                </tr>
            {/foreach}
            </tbody>
        </table>
    {else}
        <div class="alert alert-info">
            {l s='Vous n\'avez aucune location pour le moment.' mod='rentalroute'}
        </div>
    {/if}
{/block}