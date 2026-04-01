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

        $this->content = Str::markdown($markdown);

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

    public function getTitle(): string
    {
        return $this->tutorialTitle;
    }
}
