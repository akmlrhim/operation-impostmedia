<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_root_path_sends_visitors_straight_to_the_login_page(): void
    {
        $this->get(route('home'))->assertRedirect(route('login'));
    }
}
