<?php

namespace Tests\Integration\Forum;

use App\Forum\Thread;
use App\Forum\ThreadRepository;
use App\Forum\Topics\Topic;
use App\Replies\Reply;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use Tests\TestsRepositories;

class ThreadRepositoryTest extends TestCase
{
    use DatabaseMigrations, TestsRepositories;

    protected $repoName = ThreadRepository::class;

    /** @test */
    public function find_all_paginated()
    {
        $this->create(Thread::class, [], 2);

        $threads = $this->repo->findAllPaginated();

        $this->assertInstanceOf(Paginator::class, $threads);
        $this->assertCount(2, $threads);
    }

    /** @test */
    public function find_by_id()
    {
        $thread = $this->create(Thread::class, ['slug' => 'foo']);

        $this->assertInstanceOf(Thread::class, $this->repo->find($thread->id()));
    }

    /** @test */
    public function find_by_slug()
    {
        $this->create(Thread::class, ['slug' => 'foo']);

        $this->assertInstanceOf(Thread::class, $this->repo->findBySlug('foo'));
    }

    /** @test */
    function we_can_create_a_thread()
    {
        $user = $this->createUser();
        $topic = $this->create(Topic::class);

        $this->assertInstanceOf(Thread::class, $this->repo->create($user, $topic, 'Foo', 'Baz'));
    }

    /** @test */
    function we_can_update_a_thread()
    {
        $thread = $this->create(Thread::class, ['body' => 'foo']);
        $this->create(Thread::class, ['body' => 'bar']);

        $this->repo->update($thread, ['body' => 'baz']);

        $this->assertEquals('baz', $this->repo->find(1)->body());

        // Make sure other records remain unaltered.
        $this->assertEquals('bar', $this->repo->find(2)->body());
    }

    /** @test */
    function we_can_delete_a_thread()
    {
        $thread = $this->create(Thread::class);

        $this->seeInDatabase('threads', ['id' => 1]);

        $this->repo->delete($thread);

        $this->notSeeInDatabase('threads', ['id' => 1]);
    }

    /** @test */
    function we_can_mark_and_unmark_a_reply_as_the_solution()
    {
        $thread = $this->create(Thread::class);
        $reply = $this->create(Reply::class, ['replyable_id' => $thread->id()]);

        $this->assertFalse($thread->isSolutionReply($reply));

        $thread = $this->repo->markSolution($reply);

        $this->assertTrue($thread->isSolutionReply($reply));

        $thread = $this->repo->unmarkSolution($thread);

        $this->assertFalse($thread->isSolutionReply($reply));
    }
}
