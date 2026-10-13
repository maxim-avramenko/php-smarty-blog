{extends file="layout.tpl"}

{block name="content"}
    <article>
        <img src="{$article.image}" alt="{$article.title}">
        <h1>{$article.title}</h1>
        <p>{$article.description}</p>
        <p>
            <time datetime="{$article.published_at}">{$article.published_at|date_format:"%d.%m.%Y"}</time>
            · просмотров: {$article.views}
        </p>
        {if $article.categories|@count > 0}
            <ul>
                {foreach $article.categories as $category}
                    <li>
                        <a href="/category/{$category.id}">{$category.name}</a>
                    </li>
                {/foreach}
            </ul>
        {/if}
        <p>{$article.content}</p>
    </article>
    {if $similar|@count > 0}
        <section>
            <h2>Похожие статьи</h2>
            <ul>
                {foreach $similar as $item}
                    <li>
                        <article>
                            <a href="/article/{$item.id}">
                                <img src="{$item.image}" alt="{$item.title}">
                                <h3>{$item.title}</h3>
                            </a>
                            <p>{$item.description}</p>
                        </article>
                    </li>
                {/foreach}
            </ul>
        </section>
    {/if}
{/block}
