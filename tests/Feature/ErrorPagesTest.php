<?php

namespace Tests\Feature;

use Tests\TestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ErrorPagesTest extends TestCase
{
    public function test_404_page_renders_cleanly()
    {
        $exception = new HttpException(404, 'Item not found');
        $view = $this->view('errors.404', ['exception' => $exception]);
        
        $view->assertSee('404');
        $view->assertSee('Page Not Found');
        $view->assertSee('Item not found');
    }

    public function test_403_page_renders_cleanly()
    {
        $exception = new HttpException(403, 'Unauthorized access');
        $view = $this->view('errors.403', ['exception' => $exception]);
        
        $view->assertSee('403');
        $view->assertSee('Access Restricted');
        $view->assertSee('Unauthorized access');
    }

    public function test_419_page_renders_cleanly()
    {
        $exception = new HttpException(419, 'Page Expired');
        $view = $this->view('errors.419', ['exception' => $exception]);
        
        $view->assertSee('419');
        $view->assertSee('Session Expired');
        $view->assertSee('Sign In Again');
    }

    public function test_500_page_renders_cleanly()
    {
        $exception = new \Exception('Database connection failed');
        $view = $this->view('errors.500', ['exception' => $exception]);
        
        $view->assertSee('500');
        $view->assertSee('Internal Server Error');
    }

    public function test_503_page_renders_cleanly()
    {
        $exception = new HttpException(503, 'System under maintenance');
        $view = $this->view('errors.503', ['exception' => $exception]);
        
        $view->assertSee('503');
        $view->assertSee('Maintenance in Progress');
    }

    public function test_401_page_renders_cleanly()
    {
        $exception = new HttpException(401, 'Unauthenticated');
        $view = $this->view('errors.401', ['exception' => $exception]);
        
        $view->assertSee('401');
        $view->assertSee('Authentication Required');
    }

    public function test_429_page_renders_cleanly()
    {
        $exception = new HttpException(429, 'Too many requests');
        $view = $this->view('errors.429', ['exception' => $exception]);
        
        $view->assertSee('429');
        $view->assertSee('Rate Limit Exceeded');
    }
}
