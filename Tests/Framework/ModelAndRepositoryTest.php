<?php

declare(strict_types=1);

namespace Tests\Framework;

use App\Core\Database;
use App\Exceptions\Database\ModelException;
use App\Modules\WebModule\Models\Page;
use App\Modules\WebModule\Repositories\PageRepository;
use App\Providers\ExceptionProvider;
use App\Utilities\Managers\SettingsManager;
use App\Utilities\Managers\System\ErrorManager;
use PDO;
use PHPUnit\Framework\TestCase;

class ModelAndRepositoryTest extends TestCase
{
    public function testModelRejectsNonFillableMassAssignment(): void
    {
        $this->expectException(ModelException::class);

        new Page([
            'id' => 1,
            'slug' => 'home',
            'title' => 'Home',
            'content' => 'Welcome',
            'is_published' => 1,
        ]);
    }

    public function testRepositoryCreatesHydratesAndSavesModels(): void
    {
        $repository = $this->createRepository();

        $page = $repository->create([
            'slug' => 'home',
            'title' => 'Home',
            'content' => 'Welcome',
            'is_published' => 1,
        ]);

        self::assertInstanceOf(Page::class, $page);
        self::assertTrue($page->exists());
        self::assertNotNull($page->getKey());
        self::assertFalse($page->isDirty());
        self::assertNotNull($page->getAttribute('created_at'));
        self::assertNotNull($page->getAttribute('updated_at'));

        $found = $repository->findOneBy(['slug' => 'home']);

        self::assertInstanceOf(Page::class, $found);
        self::assertSame('Home', $found->getAttribute('title'));

        $found->setAttribute('title', 'Homepage');
        self::assertTrue($found->isDirty('title'));

        $saved = $repository->save($found);

        self::assertSame('Homepage', $saved->getAttribute('title'));
        self::assertFalse($saved->isDirty());
        self::assertSame(1, $repository->count(['is_published' => ['>' => 0]]));
    }

    public function testRepositorySupportsPaginationAndDeletion(): void
    {
        $repository = $this->createRepository();

        foreach ([
            ['slug' => 'home', 'title' => 'Home', 'content' => 'Welcome', 'is_published' => 1],
            ['slug' => 'about', 'title' => 'About', 'content' => 'About us', 'is_published' => 1],
            ['slug' => 'contact', 'title' => 'Contact', 'content' => 'Reach us', 'is_published' => 0],
        ] as $attributes) {
            $repository->create($attributes);
        }

        $page = $repository->findOneBy(['slug' => 'about']);
        $pagination = $repository->paginate(2, 2);

        self::assertInstanceOf(Page::class, $page);
        self::assertSame(3, $pagination['total']);
        self::assertSame(2, $pagination['last_page']);
        self::assertCount(1, $pagination['data']);
        self::assertTrue($repository->deleteModel($page));
        self::assertSame(2, $repository->count([]));
    }

    private function createRepository(): PageRepository
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec(
            'CREATE TABLE pages (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                slug TEXT NOT NULL UNIQUE,
                title TEXT NOT NULL,
                content TEXT,
                is_published INTEGER NOT NULL DEFAULT 0,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            )'
        );

        return new PageRepository($this->createDatabase($pdo));
    }

    private function createDatabase(PDO $pdo): Database
    {
        $settingsManager = $this->createStub(SettingsManager::class);
        $errorManager = new ErrorManager(new ExceptionProvider());

        return new Database($settingsManager, $errorManager, $pdo);
    }
}
