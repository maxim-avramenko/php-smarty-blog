{extends file="layout.tpl"}

{block name="content"}
    <h1 class="page-title">Главная</h1>
    {if $sections|@count == 0}
        <p class="page-empty">Пока нет категорий со статьями.</p>
    {else}
        {foreach $sections as $section}
            <section class="category-section">
                <header class="category-section__header">
                    <h2 class="category-section__title">{$section.category.name}</h2>
                    <p class="category-section__desc">{$section.category.description}</p>
                </header>
                <ul class="article-grid">
                    {foreach $section.articles as $article}
                        <li>
                            <article class="article-card">
                                <a class="article-card__link" href="/article/{$article.id}">
                                    <img class="article-card__image" src="{$article.image}" alt="{$article.title}">
                                    <h3 class="article-card__title">{$article.title}</h3>
                                </a>
                                <p class="article-card__excerpt">{$article.description}</p>
                                <time class="article-card__date" datetime="{$article.published_at}">{$article.published_at|date_format:"%d.%m.%Y"}</time>
                            </article>
                        </li>
                    {/foreach}
                </ul>
                <p class="category-section__more">
                    <a class="button" href="/category/{$section.category.id}">Все статьи</a>
                </p>
            </section>
        {/foreach}
    {/if}
{/block}
