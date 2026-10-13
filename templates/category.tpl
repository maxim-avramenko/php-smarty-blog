{extends file="layout.tpl"}

{block name="content"}
    <h1 class="page-title">{$category.name}</h1>
    <p class="page-lead">{$category.description}</p>
    <nav class="sort-nav">
        {if $sort == 'date'}
            <span class="sort-nav__item is-active">По дате</span>
        {else}
            <a class="sort-nav__item" href="/category/{$category.id}?sort=date">По дате</a>
        {/if}
        {if $sort == 'views'}
            <span class="sort-nav__item is-active">По просмотрам</span>
        {else}
            <a class="sort-nav__item" href="/category/{$category.id}?sort=views">По просмотрам</a>
        {/if}
    </nav>
    {if $articles|@count == 0}
        <p class="page-empty">В этой категории пока нет статей.</p>
    {else}
        <ul class="article-grid">
            {foreach $articles as $article}
                <li>
                    <article class="article-card">
                        <a class="article-card__link" href="/article/{$article.id}">
                            <img class="article-card__image" src="{$article.image}" alt="{$article.title}">
                            <h2 class="article-card__title">{$article.title}</h2>
                        </a>
                        <p class="article-card__excerpt">{$article.description}</p>
                        <time class="article-card__date" datetime="{$article.published_at}">{$article.published_at|date_format:"%d.%m.%Y"}</time>
                    </article>
                </li>
            {/foreach}
        </ul>
    {/if}
    {if $totalPages > 1}
        <nav class="pager">
            {if $page > 1}
                {if $page == 2}
                    <a class="pager__item" href="/category/{$category.id}?sort={$sort}">Предыдущая</a>
                {else}
                    <a class="pager__item" href="/category/{$category.id}?sort={$sort}&amp;page={$page - 1}">Предыдущая</a>
                {/if}
            {/if}
            {for $i=1 to $totalPages}
                {if $i == $page}
                    <span class="pager__item is-active">{$i}</span>
                {elseif $i == 1}
                    <a class="pager__item" href="/category/{$category.id}?sort={$sort}">{$i}</a>
                {else}
                    <a class="pager__item" href="/category/{$category.id}?sort={$sort}&amp;page={$i}">{$i}</a>
                {/if}
            {/for}
            {if $page < $totalPages}
                <a class="pager__item" href="/category/{$category.id}?sort={$sort}&amp;page={$page + 1}">Следующая</a>
            {/if}
        </nav>
    {/if}
{/block}
