<?php

declare(strict_types=1);

namespace Workbench\Database\Seeders;

use Carbon\CarbonInterface;
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
 * Builds a history worth looking at: one article written, handed to someone
 * else and then published, so the timeline has an enum, a date, a foreign key
 * and a boolean to render rather than a single "created" entry.
 *
 * Everything here is written in one go, which would leave every entry stamped
 * the same second, so each one is backdated afterwards to the age it would
 * have had in a real application.
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
            'due_date' => now()->addDays(4)->toDateString(),
            'is_featured' => false,
        ]);

        $this->backdateLastActivity(now()->subDays(8)->setTime(9, 14));

        // Handed over to the other author, with the deadline pushed back and
        // the article promoted at the same time.
        CauserResolver::setCauser($sam);

        $article->update([
            'author_id' => $sam->id,
            'due_date' => now()->addDays(18)->toDateString(),
            'is_featured' => true,
        ]);

        $this->backdateLastActivity(now()->subDays(3)->setTime(14, 8));

        // Publishing is a single thing that happened, so it is logged as one
        // entry under its own event name rather than as a generic update. The
        // write itself is silenced and the before/after is passed in by hand.
        CauserResolver::setCauser($editor);

        $publishedAt = now()->subHours(3)->startOfHour();

        activity()->withoutLogs(fn () => $article->update([
            'status' => ArticleStatus::Published,
            'published_at' => $publishedAt,
        ]));

        activity()
            ->performedOn($article)
            ->withProperties([
                'old' => [
                    'status' => ArticleStatus::Draft->value,
                    'published_at' => null,
                ],
                'attributes' => [
                    'status' => ArticleStatus::Published->value,
                    'published_at' => $publishedAt->toDateTimeString(),
                ],
            ])
            ->event('published')
            ->log('published');

        $this->backdateLastActivity($publishedAt);

        $this->seedSupportingArticles($alex, $sam);
    }

    /**
     * The rest of the list, so the resource has something to filter. One of
     * them carries a revision, which is what the timeline action's
     * withRelations() option pulls in.
     */
    private function seedSupportingArticles(Author $alex, Author $sam): void
    {
        $age = 26;

        foreach ([
            ['Shipping a design system on a budget', ArticleStatus::Published, $alex],
            ['What we learned migrating to queues', ArticleStatus::InReview, $sam],
            ['A quieter approach to notifications', ArticleStatus::Draft, $alex],
            ['Retiring the legacy importer', ArticleStatus::Archived, $sam],
        ] as $index => [$title, $status, $author]) {
            $extra = Article::query()->create([
                'title' => $title,
                'status' => ArticleStatus::Draft,
                'author_id' => $author->id,
            ]);

            $this->backdateLastActivity(now()->subDays($age)->setTime(10, 5));

            if ($index === 0) {
                $revision = Revision::query()->create([
                    'article_id' => $extra->id,
                    'summary' => 'Restructured the caching section',
                    'word_count' => 1180,
                ]);

                $this->backdateLastActivity(now()->subDays($age - 1)->setTime(11, 2));

                $revision->update(['word_count' => 1420]);

                $this->backdateLastActivity(now()->subDays($age - 2)->setTime(16, 45));
            }

            $extra->update(['status' => $status]);

            $this->backdateLastActivity(now()->subDays($age - 3)->setTime(15, 20));

            $age -= 5;
        }
    }

    /**
     * Move the entry that was just written back in time. Activities are
     * created with the current timestamp and nothing in activitylog lets you
     * set it up front, so it is rewritten here.
     */
    private function backdateLastActivity(CarbonInterface $at): void
    {
        $id = Activity::query()->max('id');

        if ($id === null) {
            return;
        }

        Activity::query()
            ->whereKey($id)
            ->update(['created_at' => $at, 'updated_at' => $at]);
    }
}
