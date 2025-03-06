<?php

namespace Webkul\Blog\Livewire\Front;

use Livewire\Component;
use Webkul\Blog\Models\Blog;

class ListBlogs extends Component
{
    public function render()
    {
        return view('blogs::livewire.front.list-blogs', [
            'blogs' => Blog::query()->paginate(9),
        ]);
    }
}
