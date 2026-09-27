<?php

namespace App\Filament\Tenant\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class TutorialViewer extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.tenant.pages.tutorial-viewer';

    protected static ?string $slug = 'tutorials/{slug}';

    protected static bool $shouldRegisterNavigation = false;

    public $tutorialSlug;
    public $content;
    public $tutorialTitle;

    public function mount($slug)
    {
        $this->tutorialSlug = $slug;
        $path = resource_path("markdown/tutorials/{$slug}.md");

        if (!File::exists($path)) {
            abort(404);
        }

        $markdown = File::get($path);

        // Extract title from the first H1 if exists, or use slug
        if (preg_match('/^#\s+(.+)$/m', $markdown, $matches)) {
            $this->tutorialTitle = $matches[1];
            // Remove the title from the content to avoid duplication
            $markdown = preg_replace('/^#\s+.+$/m', '', $markdown, 1);
        } else {
            $this->tutorialTitle = Str::title(str_replace('-', ' ', $slug));
        }

        $this->content = Str::markdown($this->interpolatePlatform($markdown));

        // Check for specific tutorial image, prioritizing cover version
        $coverPath = "images/tutorials/{$slug}-cover.png";
        $imagePath = "images/tutorials/{$slug}.png";

        if (File::exists(public_path($coverPath))) {
            $this->image = asset($coverPath);
        } elseif (File::exists(public_path($imagePath))) {
            $this->image = asset($imagePath);
        } else {
            $this->image = asset('images/tutorials/placeholder.png');
        }
    }

    public $image;

    /**
     * Parametrización de los tutoriales (épica open-core, WS4 · T4.6).
     *
     * Cada `.md` de `resources/markdown/tutorials` puede referirse a la
     * plataforma con dos placeholders, que se reemplazan por el fragmento de
     * markdown que encaja en la oración:
     *
     *   {{ plataforma }} → enlace a config('platform.platform_url')
     *                      (sin config: "la plataforma")
     *   {{ bot }}        → cláusula con el bot de config('platform.telegram_bot_username')
     *                      (sin config: "busca el bot de tu instalación en Telegram")
     *
     * Así una instalación sin `PLATFORM_*` no muestra el dominio ni el bot de
     * otra, y la instalación que los configura ve su propio link. La sustitución
     * corre antes del parseo de markdown para que el link renderice como link.
     */
    private function interpolatePlatform(string $markdown): string
    {
        return str_replace(
            ['{{ plataforma }}', '{{ bot }}'],
            [$this->platformLink(), $this->telegramBotClause()],
            $markdown,
        );
    }

    private function platformLink(): string
    {
        $url = trim((string) config('platform.platform_url'));

        if ($url === '') {
            return 'la plataforma';
        }

        return '[' . (parse_url($url, PHP_URL_HOST) ?: $url) . '](' . $url . ')';
    }

    private function telegramBotClause(): string
    {
        $username = trim(ltrim((string) config('platform.telegram_bot_username'), '@'));

        if ($username === '') {
            return 'busca el bot de tu instalación en Telegram';
        }

        return 'busca nuestro bot oficial en Telegram: **[@' . $username . '](https://t.me/' . $username . ')**';
    }

    public function getTitle(): string
    {
        return $this->tutorialTitle;
    }
}
