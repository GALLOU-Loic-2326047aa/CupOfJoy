<button class="btn btn-primary" type="button"
        {if $categoryList[0]['multiselect']==true}
            onclick="window.location.href='{$link->getModuleLink('rentFunnel', 'chooseProductMultiple')}'"
        {else}
            onclick="window.location.href='{$link->getModuleLink('rentFunnel', 'chooseProductSimple')}'"
        {/if}
    >
    Formule
</button>