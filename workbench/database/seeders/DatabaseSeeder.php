<?php

declare(strict_types=1);

namespace Workbench\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Activitylog\Facades\CauserResolver;
use Spatie\Activitylog\Models\Activity;
use Workbench\App\Models\Article;
use Workbench\App\Models\ArticleStatus;
use Workbench\App\Models\Author;
use Workbench\App\Models\Revision;
use Workbench\App\Models\User;

/**
 * Builds a history worth looking at: the same records edited by different
 * people over time, so the timeline has enum, date, foreign-key and boolean
 * changes to render rather than a single "created" entry.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Re-runnable: the demo is reseeded whenever the screenshots or the
        // sample history need to change.
        Activity::query()->delete();
        Revision::query()->delete();
        Article::query()->delete();
        Author::query()->delete();
        User::query()->where('email', 'demo@example.com')->delete();

        $editor = User::query()->create([
            'name' => 'Jane Doe',
            'email' => 'demo@example.com',
            'password' => Hash::make('password'),
        ]);

        $alex = Author::query()->create(['name' => 'Alex Rivera', 'email' => 'alex@example.com']);
        $sam = Author::query()->create(['name' => 'Sam Okafor', 'email' => 'sam@example.com']);

        CauserResolver::setCauser($editor);

        $article = Article::query()->create([
            'title' => 'Designing for offline first',
            'status' => ArticleStatus::Draft,
            'author_id' => $alex->id,
            'due_date' => '2026-07-31',
            'reading_minutes' => 6,
        ]);

        $revision = Revision::query()->create([
            'article_id' => $article->id,
            'summary' => 'Restructured the caching section',
            'word_count' => 1180,
        ]);

        $article->update([
            'status' => ArticleStatus::InReview,
            'author_id' => $sam->id,
            'reading_minutes' => 9,
        ]);

        $revision->update(['word_count' => 1420]);

        // An author acting on their own piece, so the timeline shows a causer
        // that is not a back-office user.
        CauserResolver::setCauser($sam);

        $article->update([
            'due_date' => '2026-08-14',
            'is_featured' => true,
        ]);

        // An unattended write: nobody signed in, so it renders as System.
        CauserResolver::setCauser(null);

        $article->update([
            'status' => ArticleStatus::Published,
            'published_at' => '2026-07-24 09:30:00',
        ]);

        activity()->performedOn($article)->event('published')->log('published');

        CauserResolver::setCauser($editor);

        foreach ([
            ['Shipping a design system on a budget', ArticleStatus::Published, $alex],
            ['What we learned migrating to queues', ArticleStatus::InReview, $sam],
            ['A quieter approach to notifications', ArticleStatus::Draft, $alex],
            ['Retiring the legacy importer', ArticleStatus::Archived, $sam],
        ] as [$title, $status, $author]) {
            $extra = Article::query()->create([
                'title' => $title,
                'status' => ArticleStatus::Draft,
                'author_id' => $author->id,
                'reading_minutes' => random_int(4, 12),
            ]);

            $extra->update(['status' => $status]);
        }
    }
}
