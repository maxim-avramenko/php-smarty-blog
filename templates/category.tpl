{extends file="layout.tpl"}

{block name="content"}
    <h1>{$category.name}</h1>
    <p>{$category.description}</p>
    <nav>
        {if $sort == 'date'}
            <span>По дате</span>
        {else}
            <a href="/category/{$category.id}?sort=date">По дате</a>
        {/if}
        {if $sort == 'views'}
            <span>По просмотрам</span>
        {else}
            <a href="/category/{$category.id}?sort=views">По просмотрам</a>
        {/if}
    </nav>
    {if $articles|@count == 0}
        <p>В этой категории пока нет статей.</p>
    {else}
        <ul>
            {foreach $articles as $article}
                <li>
                    <article>
                        <a href="/article/{$article.id}">
                            <img src="{$article.image}" alt="{$article.title}">
                            <h2>{$article.title}</h2>
                        </a>
                        <p>{$article.description}</p>
                        <time datetime="{$article.published_at}">{$article.published_at|date_format:"%d.%m.%Y"}</time>
                    </article>
                </li>
            {/foreach}
        </ul>
    {/if}
    {if $totalPages > 1}
        <nav>
            {if $page > 1}
                {if $page == 2}
                    <a href="/category/{$category.id}?sort={$sort}">Предыдущая</a>
                {else}
                    <a href="/category/{$category.id}?sort={$sort}&amp;page={$page - 1}">Предыдущая</a>
                {/if}
            {/if}
            {for $i=1 to $totalPages}
                {if $i == $page}
                    <span>{$i}</span>
                {elseif $i == 1}
                    <a href="/category/{$category.id}?sort={$sort}">{$i}</a>
                {else}
                    <a href="/category/{$category.id}?sort={$sort}&amp;page={$i}">{$i}</a>
                {/if}
            {/for}
            {if $page < $totalPages}
                <a href="/category/{$category.id}?sort={$sort}&amp;page={$page + 1}">Следующая</a>
            {/if}
        </nav>
    {/if}
{/block}
