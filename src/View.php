<?php

declare(strict_types=1);

namespace App;

use Smarty\Smarty;

final class View
{
    private Smarty $smarty;

    public function __construct(string $root)
    {
        $this->smarty = new Smarty();
        $this->smarty->setTemplateDir($root . '/templates');
        $this->smarty->setCompileDir($root . '/templates_c');
        $this->smarty->setEscapeHtml(true);
    }

    /**
     * @param array<string, mixed> $vars
     */
    public function render(string $template, array $vars = []): void
    {
        foreach ($vars as $key => $value) {
            $this->smarty->assign($key, $value);
        }

        $this->smarty->display($template);
    }
}
