{extends file="layout.tpl"}

{block name="content"}
    <article class="article-page">
        <img class="article-page__cover" src="{$article.image}" alt="{$article.title}">
        <h1 class="page-title">{$article.title}</h1>
        <p class="article-page__lede">{$article.description}</p>
        <p class="article-page__meta">
            <time datetime="{$article.published_at}">{$article.published_at|date_format:"%d.%m.%Y"}</time>
            · просмотров: {$article.views}
        </p>
        {if $article.categories|@count > 0}
            <ul class="article-page__categories">
                {foreach $article.categories as $category}
                    <li>
                        <a class="article-page__category" href="/category/{$category.id}">{$category.name}</a>
                    </li>
                {/foreach}
            </ul>
        {/if}
        <p class="article-page__body">{$article.content}</p>
    </article>
    {if $similar|@count > 0}
        <section class="similar">
            <h2 class="similar__title">Похожие статьи</h2>
            <ul class="article-grid">
                {foreach $similar as $item}
                    <li>
                        <article class="article-card">
                            <a class="article-card__link" href="/article/{$item.id}">
                                <img class="article-card__image" src="{$item.image}" alt="{$item.title}">
                                <h3 class="article-card__title">{$item.title}</h3>
                            </a>
                            <p class="article-card__excerpt">{$item.description}</p>
                        </article>
                    </li>
                {/foreach}
            </ul>
        </section>
    {/if}
{/block}
