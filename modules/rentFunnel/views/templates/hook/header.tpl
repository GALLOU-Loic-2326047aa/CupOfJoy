<div id="recap-container" class="recap-container">
    <button id="btn-recap" class="btn-recap" type="button">
        <span>
            Mon abonnement
        </span>
        <i class="material-icons header-block__icon" aria-hidden="true">shopping_cart</i>
    </button>
    <div id="recap-panel" class="recap-details" style="display: none;">
        <div>
            <strong class="dark">Machine choisie :</strong>
            {if isset($machine_id)}
            <span>{$machine_name}</span>
            {/if}
        </div>
        <div>
            <strong class="dark">Café choisi :</strong>
            {if isset($coffee_id)}
            <span>{$coffee_name}</span>
            {/if}
        </div>
        <div>
            <strong class="dark">Accessoire choisi :</strong>
            {if isset($accessory_id)}
                {if !is_bool($accessory_name)}
                    <span>{$accessory_name}</span>
                {else}
                    <span>aucun</span>
                {/if}
            {/if}
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const btn = document.getElementById('btn-recap');
        const panel = document.getElementById('recap-panel');

        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
        });

        document.addEventListener('click', function () {
            panel.style.display = 'none';
        });

        panel.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    });
</script>