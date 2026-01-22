{function name="categories" nodes=[] depth=0}
    {strip}
        {if $nodes|count}
            <ul class="category-sub-menu">
                {foreach from=$nodes item=node}
                    <li data-depth="{$depth}">
                        {if $depth===0}
                            <a href="{$node.link}">{$node.name}</a>
                            {if $node.children}
                                <div class="navbar-toggler collapse-icons" data-toggle="collapse" data-target="#exCollapsingNavbar{$node.id}">
                                    <i class="material-icons add">&#xE145;</i>
                                    <i class="material-icons remove">&#xE15B;</i>
                                </div>
                                <div class="collapse" id="exCollapsingNavbar{$node.id}">
                                    {categories nodes=$node.children depth=$depth+1}
                                </div>
                            {/if}
                        {else}
                            <a class="category-sub-link" href="{$node.link}">{$node.name}</a>
                            {if $node.children}
                                <span class="arrows" data-toggle="collapse" data-target="#exCollapsingNavbar{$node.id}">
                  <i class="material-icons arrow-right">&#xE315;</i>
                  <i class="material-icons arrow-down">&#xE313;</i>
                </span>
                                <div class="collapse" id="exCollapsingNavbar{$node.id}">
                                    {categories nodes=$node.children depth=$depth+1}
                                </div>
                            {/if}
                        {/if}
                    </li>
                {/foreach}
            </ul>
        {/if}
    {/strip}
{/function}

<div class="block-categories nespresso-category-block">
    <ul class="category-top-menu">
        <li><a class="text-uppercase h6 nespresso-parent-category" href="{$categories.link nofilter}">{$categories.name}</a></li>
        {if !empty($categories.children)}
            <li>{categories nodes=$categories.children}</li>
        {/if}
    </ul>
</div>