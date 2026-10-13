{extends file="layout.tpl"}

{block name="content"}
    <h1>Главная</h1>
    {if $sections|@count == 0}
        <p>Пока нет категорий со статьями.</p>
    {else}
        {foreach $sections as $section}
            <section>
                <h2>{$section.category.name}</h2>
                <p>{$section.category.description}</p>
                <ul>
                    {foreach $section.articles as $article}
                        <li>
                            <article>
                                <a href="/article/{$article.id}">
                                    <img src="{$article.image}" alt="{$article.title}">
                                    <h3>{$article.title}</h3>
                                </a>
                                <p>{$article.description}</p>
                                <time datetime="{$article.published_at}">{$article.published_at|date_format:"%d.%m.%Y"}</time>
                            </article>
                        </li>
                    {/foreach}
                </ul>
                <p>
                    <a href="/category/{$section.category.id}">Все статьи</a>
                </p>
            </section>
        {/foreach}
    {/if}
{/block}
