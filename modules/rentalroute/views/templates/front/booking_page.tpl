{extends file='page.tpl'}

{block name='page_content'}
    <h1>{l s='Réservation de votre location' mod='rentalroute'}</h1>

    {if isset($booking_success_message) && $booking_success_message}
        <div class="alert alert-success">
            {$booking_success_message}
        </div>

        <a href="{$my_rentals_url}" class="btn btn-primary">
            {l s='Voir mes locations' mod='rentalroute'}
        </a>
    {/if}
{/block}